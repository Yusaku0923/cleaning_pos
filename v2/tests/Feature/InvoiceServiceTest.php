<?php

namespace Tests\Feature;

use App\Domain\Billing\InvoiceService;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 請求書 (旧 OrdersController::store / ReturnController / Api\InvoiceController::carry_over / align_cutoff_date,
 * Invoice::existsTargetInvoice / isMismatchCutoffDate)。
 */
class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $t = ['created_at' => now(), 'updated_at' => now()];
        $store = DB::table('stores')->insertGetId(['name' => '店', 'email' => 's@example.com', 'password' => 'x', 'address' => '住所', 'phone_number' => '000'] + $t);
        $this->manager = DB::table('managers')->insertGetId(['store_id' => $store, 'name' => '担当'] + $t);
    }

    private function customer(int $cutoff, bool $needsPaymentConfirmation = false): Customer
    {
        return Customer::create([
            'manager_id' => $this->manager, 'name' => '客', 'name_kana' => 'キャク',
            'cutoff_date' => $cutoff, 'is_invoice' => true,
            'needs_payment_confirmation' => $needsPaymentConfirmation,
        ]);
    }

    private function service(): InvoiceService
    {
        return app(InvoiceService::class);
    }

    private function period(Invoice $i): array
    {
        return [$i->period_start->toDateString(), $i->period_end->toDateString()];
    }

    private function order(Customer $c, int $invoiceId, ?string $handedAt): Order
    {
        return Order::create([
            'store_id' => DB::table('managers')->where('id', $this->manager)->value('store_id'),
            'manager_id' => $this->manager, 'customer_id' => $c->id, 'invoice_id' => $invoiceId,
            'is_invoice' => true, 'amount' => 1000, 'reduction' => 0, 'payment' => 0, 'handed_at' => $handedAt,
        ]);
    }

    public function test_needs_payment_confirmation_customer_gets_paid_at_period_end(): void
    {
        $c = $this->customer(20, needsPaymentConfirmation: true);

        $invoice = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-03-25 14:00:00'));

        $this->assertSame(['2026-03-21', '2026-04-20'], $this->period($invoice));
        $this->assertSame('2026-04-20', $invoice->fresh()->paid_at->toDateString());
        $this->assertSame($this->manager, $invoice->manager_id);
        $this->assertSame($c->id, $invoice->customer_id);
    }

    public function test_other_customer_paid_at_null(): void
    {
        $c = $this->customer(99);

        $invoice = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-02-10'));

        $this->assertSame(['2026-02-01', '2026-02-28'], $this->period($invoice->fresh()));
        $this->assertNull($invoice->fresh()->paid_at);
    }

    public function test_same_period_reuses_existing_invoice(): void
    {
        $c = $this->customer(99, needsPaymentConfirmation: true);

        $a = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-02-01'));
        $b = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-02-28 23:59:59'));
        $next = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-03-01'));

        $this->assertSame($a->id, $b->id);
        $this->assertNotSame($a->id, $next->id);
        $this->assertSame(2, Invoice::where('customer_id', $c->id)->count());
    }

    public function test_soft_deleted_invoice_is_not_reused(): void
    {
        $c = $this->customer(99);
        $a = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-02-01'));
        $a->delete();

        $b = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-02-02'));

        $this->assertNotSame($a->id, $b->id);
    }

    public function test_cutoff_zero_customer_gets_a_real_month_end(): void
    {
        $c = $this->customer(0);

        $invoice = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-06-23 15:59:24'));

        $this->assertSame(['2026-06-01', '2026-06-30'], $this->period($invoice->fresh()));
        $this->assertSame('2026-06-30', DB::table('invoices')->where('id', $invoice->id)->value('period_end'));
    }

    public function test_carry_over_creates_next_period_invoice(): void
    {
        $c = $this->customer(20);
        $from = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-01-10')); // 12-21 〜 01-20

        $next = $this->service()->carryOver($from);

        $this->assertSame(['2026-01-21', '2026-02-20'], $this->period($next->fresh()));
        $this->assertTrue($next->fresh()->has_carried_over);
        $this->assertNull($next->fresh()->paid_at);
        $this->assertSame($next->id, $from->fresh()->carry_over_id);
        $this->assertFalse($from->fresh()->has_carried_over);
    }

    public function test_carry_over_reuses_existing_next_invoice_and_moves_children(): void
    {
        $c = $this->customer(99);
        $dec = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2025-12-05'));
        $jan = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-01-05'));
        $feb = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-02-05'));

        // 12 月 → 1 月へ繰越 (1 月は既にある)
        $this->assertSame($jan->id, $this->service()->carryOver($dec)->id);
        $this->assertTrue($jan->fresh()->has_carried_over);
        $this->assertSame($jan->id, $dec->fresh()->carry_over_id);

        // 1 月 (繰越を受けている) → 2 月: 12 月の繰越先も 2 月に付け替え、1 月の繰越フラグを折る
        $this->assertSame($feb->id, $this->service()->carryOver($jan)->id);
        $this->assertSame($feb->id, $dec->fresh()->carry_over_id);
        $this->assertSame($feb->id, $jan->fresh()->carry_over_id);
        $this->assertFalse($jan->fresh()->has_carried_over);
        $this->assertTrue($feb->fresh()->has_carried_over);
        $this->assertSame(3, Invoice::where('customer_id', $c->id)->count());
    }

    public function test_align_cutoff_moves_orders_of_the_selected_invoice_and_deletes_it(): void
    {
        $c = $this->customer(99);
        // 締め日を 20 日 → 末日に変えた顧客の古い請求書 (period_end の日 ≠ 締め日)
        $old = Invoice::create(['manager_id' => $this->manager, 'customer_id' => $c->id, 'period_start' => '2026-01-21', 'period_end' => '2026-02-20']);
        $other = Invoice::create(['manager_id' => $this->manager, 'customer_id' => $c->id, 'period_start' => '2025-12-21', 'period_end' => '2026-01-20']);
        $ok = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-03-05')); // 03-01 〜 03-31
        $o1 = $this->order($c, $old->id, '2026-01-25 10:00:00');
        $o2 = $this->order($c, $old->id, '2026-02-03 10:00:00');
        $o3 = $this->order($c, $ok->id, '2026-03-05 10:00:00');
        $o4 = $this->order($c, $other->id, '2026-01-10 10:00:00');

        $this->assertTrue($this->service()->isMismatchCutoff($old));
        $this->assertTrue($this->service()->isMismatchCutoff($other));
        $this->assertFalse($this->service()->isMismatchCutoff($ok));

        // 旧 align_cutoff_date と同じく、選んだ 1 件だけを直す
        $this->service()->alignCutoff($old);

        $jan = Invoice::where('customer_id', $c->id)->whereDate('period_end', '2026-01-31')->sole();
        $feb = Invoice::where('customer_id', $c->id)->whereDate('period_end', '2026-02-28')->sole();
        $this->assertSame($jan->id, $o1->fresh()->invoice_id);
        $this->assertSame($feb->id, $o2->fresh()->invoice_id);
        $this->assertNull($jan->paid_at);
        $this->assertSame($this->manager, $jan->manager_id);
        $this->assertSoftDeleted('invoices', ['id' => $old->id]);
        // 選んでいない請求書と、その注文はそのまま
        $this->assertSame($ok->id, $o3->fresh()->invoice_id);
        $this->assertSame($other->id, $o4->fresh()->invoice_id);
        $this->assertNotSoftDeleted('invoices', ['id' => $other->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $ok->id]);
    }

    public function test_align_cutoff_never_moves_orders_onto_the_invoice_being_deleted(): void
    {
        // 締め日 0 の請求書は旧の判定で常に「ズレ」。正しい期間が自分自身と同じでも、消える請求書には移さない
        $c = $this->customer(0);
        $inv = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-06-23')); // 06-01 〜 06-30
        $o = $this->order($c, $inv->id, '2026-06-23 10:00:00');
        $this->assertTrue($this->service()->isMismatchCutoff($inv));

        $this->service()->alignCutoff($inv);

        $this->assertSoftDeleted('invoices', ['id' => $inv->id]);
        $target = Invoice::find($o->fresh()->invoice_id);
        $this->assertNotNull($target);
        $this->assertSame(['2026-06-01', '2026-06-30'], $this->period($target));
    }

    /** 旧から取り込んだ締め日 0 の顧客の請求書 (period_end = 翌月の '-00')。 */
    private function insertLegacyDay00Invoice(Customer $c, string $start, string $end): int
    {
        $mode = DB::selectOne('select @@session.sql_mode as m')->m;
        DB::statement("set session sql_mode = ''");
        try {
            return DB::table('invoices')->insertGetId([
                'manager_id' => $this->manager, 'customer_id' => $c->id,
                'period_start' => $start, 'period_end' => $end,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } finally {
            DB::statement('set session sql_mode = ?', [$mode]);
        }
    }

    public function test_imported_day_00_invoice_is_reused_for_the_same_period(): void
    {
        // 本番 請求書 1281: 締め日 0、06-23 渡し → 旧は 2026-06-01 .. 2026-07-00 (= 6 月分)
        $c = $this->customer(0);
        $legacyId = $this->insertLegacyDay00Invoice($c, '2026-10-01', '2026-11-00');
        $this->assertSame('2026-11-00', DB::table('invoices')->where('id', $legacyId)->value('period_end'));

        $invoice = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-10-23 12:00:00'));

        $this->assertSame($legacyId, $invoice->id);
        $this->assertSame(1, Invoice::where('customer_id', $c->id)->count());
        // 保存されている値は変えない
        $this->assertSame('2026-11-00', DB::table('invoices')->where('id', $legacyId)->value('period_end'));
    }

    public function test_day_00_invoice_of_another_month_is_not_reused(): void
    {
        // '2026-10-00' は 9 月分 (2026-09-01 .. 2026-10-00)。10 月の注文には使わない
        $c = $this->customer(0);
        $september = $this->insertLegacyDay00Invoice($c, '2026-09-01', '2026-10-00');

        $invoice = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-10-23'));

        $this->assertNotSame($september, $invoice->id);
        $this->assertSame(['2026-10-01', '2026-10-31'], $this->period($invoice->fresh()));
        // 9 月の注文なら旧の請求書を使う
        $this->assertSame($september, $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-09-15'))->id);
    }
}
