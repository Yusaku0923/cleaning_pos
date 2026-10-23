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

    public function test_align_cutoff_moves_orders_of_mismatched_invoices_and_deletes_them(): void
    {
        $c = $this->customer(99);
        // 締め日を 20 日 → 末日に変えた顧客の古い請求書 (period_end の日 ≠ 締め日)
        $old = Invoice::create(['manager_id' => $this->manager, 'customer_id' => $c->id, 'period_start' => '2026-01-21', 'period_end' => '2026-02-20']);
        $ok = $this->service()->findOrCreateFor($c, CarbonImmutable::parse('2026-03-05')); // 03-01 〜 03-31
        $o1 = $this->order($c, $old->id, '2026-01-25 10:00:00');
        $o2 = $this->order($c, $old->id, '2026-02-03 10:00:00');
        $o3 = $this->order($c, $ok->id, '2026-03-05 10:00:00');

        $this->assertTrue($this->service()->isMismatchCutoff($old));
        $this->assertFalse($this->service()->isMismatchCutoff($ok));

        $this->service()->alignCutoff($c);

        $jan = Invoice::where('customer_id', $c->id)->whereDate('period_end', '2026-01-31')->sole();
        $feb = Invoice::where('customer_id', $c->id)->whereDate('period_end', '2026-02-28')->sole();
        $this->assertSame($jan->id, $o1->fresh()->invoice_id);
        $this->assertSame($feb->id, $o2->fresh()->invoice_id);
        $this->assertSame($ok->id, $o3->fresh()->invoice_id);
        $this->assertSoftDeleted('invoices', ['id' => $old->id]);
        $this->assertNotSoftDeleted('invoices', ['id' => $ok->id]);
    }
}
