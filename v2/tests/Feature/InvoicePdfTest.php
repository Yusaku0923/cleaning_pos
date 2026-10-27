<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Support\LegacyFixtureDatabase;
use Tests\TestCase;

require_once __DIR__.'/../Support/invoice_pdf_summary.php';

/**
 * 請求書の一覧・PDF・発行記録・繰越・締日整列・入金確認、日報と PDF
 * (旧 InvoiceController / Api\InvoiceController / DailyReportController)。
 * PDF は GET では表示だけ。発行済みの記録 (issued_at) は POST /invoice/issue で入れる (旧は GET で更新していた)。
 */
class InvoicePdfTest extends TestCase
{
    use LegacyFixtureDatabase;
    use RefreshDatabase;

    private Store $store;

    private int $manager;

    private int $otherManager;

    private int $customer;

    private int $confirmCustomer;

    private int $shirt;

    private int $blanket;

    private function t(): array
    {
        return ['created_at' => now(), 'updated_at' => now()];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow('2026-10-23 10:15:00');

        $storeId = DB::table('stores')->insertGetId(['name' => 'あさひ屋', 'email' => 'a@example.com', 'password' => 'x', 'postal_code' => '877-0000', 'address' => '日田市', 'phone_number' => '0973-00-0000'] + $this->t());
        $otherId = DB::table('stores')->insertGetId(['name' => '他店', 'email' => 'b@example.com', 'password' => 'x', 'address' => '', 'phone_number' => ''] + $this->t());
        $this->store = Store::findOrFail($storeId);
        $this->manager = DB::table('managers')->insertGetId(['store_id' => $storeId, 'name' => '若宮本店'] + $this->t());
        $this->otherManager = DB::table('managers')->insertGetId(['store_id' => $otherId, 'name' => '他'] + $this->t());

        $this->customer = DB::table('customers')->insertGetId(['manager_id' => $this->manager, 'name' => '山田 太郎', 'name_kana' => 'ﾔﾏﾀﾞ', 'cutoff_date' => 99, 'is_invoice' => true] + $this->t());
        $this->confirmCustomer = DB::table('customers')->insertGetId(['manager_id' => $this->manager, 'name' => '日田病院', 'name_kana' => 'ﾋﾀﾋﾞｮｳｲﾝ', 'cutoff_date' => 20, 'is_invoice' => true, 'needs_payment_confirmation' => true] + $this->t());

        $cat = DB::table('categories')->insertGetId(['store_id' => $storeId, 'name' => 'カテゴリ'] + $this->t());
        $this->shirt = DB::table('clothes')->insertGetId(['store_id' => $storeId, 'category_id' => $cat, 'name' => 'ワイシャツ', 'name_kana' => 'ﾜｲｼｬﾂ', 'price' => 220, 'tag_count' => 1] + $this->t());
        $this->blanket = DB::table('clothes')->insertGetId(['store_id' => $storeId, 'category_id' => $cat, 'name' => '毛布', 'name_kana' => 'ﾓｳﾌ', 'price' => 1200, 'tag_count' => 1] + $this->t());
    }

    private function invoice(int $customerId, string $start, string $end, array $over = []): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'manager_id' => $this->manager, 'customer_id' => $customerId,
            'period_start' => $start, 'period_end' => $end,
        ], $over) + $this->t());
    }

    /** 請求書に注文を 1 件付ける。$items = [[clothes_id, tag], ...]。金額は単価の合計 */
    private function order(int $invoiceId, int $customerId, array $items, string $handedAt = '2026-10-10 12:00:00', array $over = []): int
    {
        $prices = DB::table('clothes')->pluck('price', 'id');
        $amount = array_sum(array_map(fn ($i) => (int) $prices[$i[0]], $items));
        $orderId = DB::table('orders')->insertGetId(array_merge([
            'store_id' => $this->store->id, 'manager_id' => $this->manager, 'customer_id' => $customerId,
            'invoice_id' => $invoiceId, 'is_invoice' => true, 'amount' => $amount, 'reduction' => 0, 'discount' => 0,
            'payment' => 0, 'handed_at' => $handedAt, 'created_at' => '2026-10-05 09:00:00', 'updated_at' => now(),
        ], $over));
        foreach ($items as [$clothesId, $tag]) {
            DB::table('order_items')->insert(['order_id' => $orderId, 'clothes_id' => $clothesId, 'tag' => $tag] + $this->t());
        }

        return $orderId;
    }

    /** GET したときに invoice/pdf (または $view) の Blade に渡ったデータ */
    private function captureView(string $view = 'invoice.pdf'): \ArrayObject
    {
        $captured = new \ArrayObject;
        View::composer($view, function ($v) use ($captured) {
            $captured->exchangeArray($v->getData());
        });

        return $captured;
    }

    private function pdfUrl(string $ids): string
    {
        return '/invoice/pdf?m='.$this->manager.'&ids='.urlencode($ids);
    }

    // ---- PDF / 発行 ----------------------------------------------------------

    public function test_pdf_get_does_not_modify_issued_at(): void
    {
        $id = $this->invoice($this->customer, '2026-10-01', '2026-10-31');
        $this->order($id, $this->customer, [[$this->shirt, '1-001']]);

        $res = $this->actingAs($this->store)->get($this->pdfUrl((string) $id));

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
        $this->assertNull(DB::table('invoices')->where('id', $id)->value('issued_at'));
    }

    public function test_issue_post_sets_issued_at_today(): void
    {
        $a = $this->invoice($this->customer, '2026-10-01', '2026-10-31');
        $b = $this->invoice($this->confirmCustomer, '2026-09-21', '2026-10-20', ['issued_at' => '2026-10-01']);
        $untouched = $this->invoice($this->customer, '2026-09-01', '2026-09-30');

        $this->actingAs($this->store)->postJson('/invoice/issue', ['ids' => [$a, $b]])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('2026-10-23', DB::table('invoices')->where('id', $a)->value('issued_at'));
        $this->assertSame('2026-10-23', DB::table('invoices')->where('id', $b)->value('issued_at'));
        $this->assertNull(DB::table('invoices')->where('id', $untouched)->value('issued_at'));
    }

    public function test_issue_rejects_other_store_and_non_integer_ids(): void
    {
        $other = DB::table('customers')->insertGetId(['manager_id' => $this->otherManager, 'name' => '他客', 'name_kana' => 'ﾀ'] + $this->t());
        $otherInvoice = DB::table('invoices')->insertGetId(['manager_id' => $this->otherManager, 'customer_id' => $other, 'period_start' => '2026-10-01', 'period_end' => '2026-10-31'] + $this->t());
        $mine = $this->invoice($this->customer, '2026-10-01', '2026-10-31');

        $this->actingAs($this->store)->postJson('/invoice/issue', ['ids' => [$mine, $otherInvoice]])->assertNotFound();
        $this->actingAs($this->store)->postJson('/invoice/issue', ['ids' => ['1); drop table invoices; --']])->assertStatus(422);
        $this->actingAs($this->store)->postJson('/invoice/issue', ['ids' => []])->assertStatus(422);
        $this->assertNull(DB::table('invoices')->where('id', $mine)->value('issued_at'));
        $this->assertNull(DB::table('invoices')->where('id', $otherInvoice)->value('issued_at'));
        // 他店舗の請求書は PDF でも見えない
        $this->actingAs($this->store)->get($this->pdfUrl((string) $otherInvoice))->assertNotFound();
    }

    public function test_ids_with_sql_is_rejected_422(): void
    {
        $id = $this->invoice($this->customer, '2026-10-01', '2026-10-31');

        foreach (['1,2)--', $id.') or (1=1', '', '1,,2', 'abc', '-1'] as $ids) {
            $this->actingAs($this->store)->get($this->pdfUrl($ids))->assertStatus(422);
        }
    }

    public function test_pdf_order_follows_ids_order(): void
    {
        $a = $this->invoice($this->customer, '2026-10-01', '2026-10-31');
        $b = $this->invoice($this->confirmCustomer, '2026-09-21', '2026-10-20');
        $c = $this->invoice($this->customer, '2026-09-01', '2026-09-30');
        foreach ([$a, $b, $c] as $id) {
            $this->order($id, DB::table('invoices')->where('id', $id)->value('customer_id'), [[$this->shirt, '1-001']]);
        }
        $data = $this->captureView();

        $this->actingAs($this->store)->get($this->pdfUrl("{$b},{$c},{$a}"))->assertOk();

        $this->assertSame([$b, $c, $a], array_map(fn ($p) => (int) $p['id'], $data['invoices']));
        $this->assertSame(10, (int) $data['tax']);
    }

    public function test_pdf_rows_group_consecutive_items_and_split_pages_like_legacy(): void
    {
        $id = $this->invoice($this->customer, '2026-10-01', '2026-10-31');
        $from = $this->invoice($this->customer, '2026-09-01', '2026-09-30', ['carry_over_id' => null]);
        DB::table('invoices')->where('id', $id)->update(['has_carried_over' => true]);
        DB::table('invoices')->where('id', $from)->update(['carry_over_id' => $id]);
        $this->order($from, $this->customer, [[$this->blanket, '2-001']], '2026-09-10 10:00:00');
        // 1 件目: ワイシャツ 3 点 (まとめて 1 行) + 毛布 1 点、2 件目: 毛布 40 点 (まとめて 1 行)、タグなし (0-000)
        $first = $this->order($id, $this->customer, [[$this->shirt, '1-001'], [$this->shirt, '1-002'], [$this->shirt, '1-003'], [$this->blanket, '1-004']], '2026-10-02 10:00:00');
        $second = $this->order($id, $this->customer, array_fill(0, 40, [$this->blanket, '0-000']), '2026-10-12 10:00:00');
        // 削除済みの注文・品物は載らない
        $deleted = $this->order($id, $this->customer, [[$this->shirt, '1-005']], '2026-10-13 10:00:00', ['deleted_at' => now()]);
        DB::table('order_items')->where('order_id', $first)->where('tag', '1-003')->update(['deleted_at' => now()]);
        DB::table('orders')->where('id', $first)->update(['amount' => 220 * 2 + 1200]);
        $data = $this->captureView();

        $this->actingAs($this->store)->get($this->pdfUrl((string) $id))->assertOk();

        $summary = invoice_pdf_summary($data['invoices']);
        $this->assertCount(1, $summary);
        $page = $summary[0];
        $this->assertSame('1/1', $page['page']);
        $this->assertSame('山田 太郎', $page['customer_name']);
        $this->assertSame(sprintf('%05d-261031', $id), $page['invoice_no']);
        $this->assertSame(1640 + 48000, $page['amount']);
        $this->assertSame(1200, $page['carried_over_amount']);
        $this->assertSame(1640 + 48000 + 1200, $page['billed']);
        // 内税 10% 四捨五入 (config('pos.tax_rounding.invoice'))。49640 - round(49640 / 1.1) = 4513
        $this->assertSame(['amount' => 49640, 'tax' => 4513], $page['tax_line']);
        $this->assertSame([
            ['g', '26/10/02', $first, '1-001~1-002', 'ワイシャツ', 220, 2, 440],
            ['d', '26/10/02', $first, '1-004', '毛布', 1200, 1, 1200],
            ['o', 3, 1640],
            ['g', '26/10/12', $second, '', '毛布', 1200, 40, 48000],
            ['o', 40, 48000],
        ], $page['rows']);
        $this->assertNotContains($deleted, array_column($page['rows'], 2));
    }

    public function test_pdf_tax_follows_invoice_rounding_config(): void
    {
        $id = $this->invoice($this->customer, '2026-10-01', '2026-10-31');
        $this->order($id, $this->customer, [[$this->shirt, '1-001']], over: ['amount' => 505]);
        $data = $this->captureView();

        config(['pos.tax_rounding.invoice' => 'floor']);
        $this->actingAs($this->store)->get($this->pdfUrl((string) $id))->assertOk();
        $this->assertSame(45, (int) $data['invoices'][0]['tax_amount']); // trunc(505 - 459.09)

        config(['pos.tax_rounding.invoice' => 'round']);
        $this->actingAs($this->store)->get($this->pdfUrl((string) $id))->assertOk();
        $this->assertSame(46, (int) $data['invoices'][0]['tax_amount']); // 505 - round(459.09)
    }

    public function test_pdf_without_manager_redirects_to_invoice_index(): void
    {
        $id = $this->invoice($this->customer, '2026-10-01', '2026-10-31');

        $this->actingAs($this->store)->get('/invoice/pdf?ids='.$id)->assertRedirect(route('invoice.index'));
    }

    /**
     * 移行済みデータの請求 3 件について、PDF の Blade に渡す値 (合計・税・繰越・明細) が旧と一致する。
     * 旧の値: tests/fixtures/legacy_invoice_totals.json (make_legacy_invoice_totals.php が旧の Docker で作る)。
     * その旧の行を legacy_testing に入れて legacy:import し、同じ請求書の PDF を v2 で開く。
     */
    public function test_invoice_pdf_amounts_match_legacy(): void
    {
        // legacy:import は migrate:fresh・ALTER TABLE をするので RefreshDatabase のトランザクションから抜ける
        DB::rollBack();
        try {
            $fixture = json_decode(file_get_contents(base_path('tests/fixtures/legacy_invoice_totals.json')), true, flags: JSON_THROW_ON_ERROR);
            $this->loadLegacyFixture(function (Connection $legacy) use ($fixture) {
                foreach (['order_clothes', 'orders', 'invoices', 'customer_information', 'customers', 'tag_numbers', 'clothes', 'categories', 'taxes', 'managers', 'stores'] as $table) {
                    $legacy->table($table)->delete();
                }
                foreach ($fixture['legacy_rows'] as $table => $data) {
                    foreach (array_chunk($data['rows'], 500) as $chunk) {
                        $legacy->table($table)->insert(array_map(fn ($r) => array_combine($data['columns'], $r), $chunk));
                    }
                }
            });
            $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);

            $store = Store::findOrFail($fixture['legacy_rows']['stores']['rows'][0][0]);
            $managerId = DB::table('managers')->where('store_id', $store->id)->orderBy('id')->value('id');
            $data = $this->captureView();

            $this->actingAs($store)->get('/invoice/pdf?m='.$managerId.'&ids='.implode(',', $fixture['print_ids']))->assertOk();

            $this->assertSame($fixture['tax'], (int) $data['tax']);
            $actual = invoice_pdf_summary($data['invoices']);
            // 先に合計・税・繰越を比べる (ずれたときに分かりやすく)
            $totals = fn (array $pages) => array_map(fn ($p) => array_diff_key($p, ['rows' => 1]), $pages);
            $this->assertSame($totals($fixture['expected']), $totals($actual));
            $this->assertSame($fixture['expected'], $actual);
        } finally {
            $this->resetImportedDatabase();
        }
    }

    // ---- 請求書一覧・繰越・締日整列 ------------------------------------------------

    public function test_invoice_index_lists_this_month_with_amounts(): void
    {
        $a = $this->invoice($this->customer, '2026-10-01', '2026-10-31');
        $this->order($a, $this->customer, [[$this->shirt, '1-001'], [$this->blanket, '1-002']]);
        $old = $this->invoice($this->customer, '2026-09-01', '2026-09-30');
        $mismatch = $this->invoice($this->confirmCustomer, '2026-09-21', '2026-10-15'); // 締め日 20 なのに 15 日締め

        $res = $this->actingAs($this->store)->get('/invoice?m='.$this->manager);

        $res->assertOk()->assertSee('請　求　書　発　行')->assertSee('invoice-component', false);
        $invoices = collect($res->viewData('invoices'))->keyBy('id');
        $this->assertSame([$a, $mismatch], $invoices->keys()->sort()->values()->all());
        $this->assertSame(1420, (int) $invoices[$a]['amount']);
        $this->assertSame(0, (int) $invoices[$a]['carried_over_amount']);
        $this->assertSame('2026-10-31', $invoices[$a]['period_end']);
        $this->assertSame('山田 太郎', $invoices[$a]['name']);
        $this->assertFalse($invoices[$a]['is_mismatch_cutoff_date']);
        $this->assertTrue($invoices[$mismatch]['is_mismatch_cutoff_date']);
        $this->assertArrayNotHasKey($old, $invoices->all());

        $this->actingAs($this->store)->get('/invoice')->assertRedirect(route('home'));
    }

    public function test_api_search_filters_like_legacy(): void
    {
        $sep = $this->invoice($this->customer, '2026-09-01', '2026-09-30');
        $oct = $this->invoice($this->confirmCustomer, '2026-09-21', '2026-10-20');

        $search = fn (array $conditions) => $this->actingAs($this->store)->postJson('/api/invoice/search', [
            'manager_id' => $this->manager, 'conditions' => $conditions,
        ])->assertOk()->json('invoices');

        $this->assertSame([$sep], array_column($search(['cutoff_date' => 0, 'target_month' => '2026-09', 'customer_name' => '']), 'id'));
        $this->assertSame([$oct], array_column($search(['cutoff_date' => 20, 'target_month' => '', 'customer_name' => '']), 'id'));
        $this->assertSame([$oct], array_column($search(['cutoff_date' => 0, 'target_month' => '', 'customer_name' => '病院']), 'id'));
        // 旧と同じく、選択肢の「-」('0' 文字列) は締め日 0 の顧客で絞る (数値の 0 は絞らない)
        $this->assertSame([], $search(['cutoff_date' => '0', 'target_month' => '', 'customer_name' => '']));

        $this->actingAs($this->store)->postJson('/api/invoice/search', ['manager_id' => $this->otherManager, 'conditions' => null])->assertNotFound();
    }

    public function test_api_carry_over_moves_amount_to_next_invoice(): void
    {
        $sep = $this->invoice($this->customer, '2026-09-01', '2026-09-30');
        $this->order($sep, $this->customer, [[$this->blanket, '1-001']]);

        $this->actingAs($this->store)->postJson('/api/invoice/carry_over', ['invoices' => [$sep]])->assertOk()->assertJson(['ok' => true]);

        $next = DB::table('invoices')->where('id', DB::table('invoices')->where('id', $sep)->value('carry_over_id'))->first();
        $this->assertSame(['2026-10-01', '2026-10-31', 1], [$next->period_start, $next->period_end, (int) $next->has_carried_over]);
        $listed = collect($this->actingAs($this->store)->get('/invoice?m='.$this->manager)->viewData('invoices'))->keyBy('id');
        $this->assertSame(1200, (int) $listed[$next->id]['carried_over_amount']);
    }

    public function test_api_align_cutoff_date_handles_each_selected_invoice(): void
    {
        // 締め日 99 の顧客なのに 15 日締めの請求書が 2 件 (旧は配列で受け取り、1 件ずつ処理する)
        $a = $this->invoice($this->customer, '2026-08-16', '2026-09-15');
        $b = $this->invoice($this->customer, '2026-09-16', '2026-10-15');
        $oa = $this->order($a, $this->customer, [[$this->shirt, '1-001']], '2026-09-10 10:00:00');
        $ob = $this->order($b, $this->customer, [[$this->shirt, '1-002']], '2026-10-10 10:00:00');

        $this->actingAs($this->store)->postJson('/api/invoice/align_cutoff_date', ['invoices' => [$a, $b]])->assertOk()->assertJson(['ok' => true]);

        $this->assertSoftDeleted('invoices', ['id' => $a]);
        $this->assertSoftDeleted('invoices', ['id' => $b]);
        $period = fn (int $orderId) => DB::table('invoices')->where('id', DB::table('orders')->where('id', $orderId)->value('invoice_id'))->first(['period_start', 'period_end']);
        $this->assertEquals((object) ['period_start' => '2026-09-01', 'period_end' => '2026-09-30'], $period($oa));
        $this->assertEquals((object) ['period_start' => '2026-10-01', 'period_end' => '2026-10-31'], $period($ob));
    }

    public function test_api_invoice_operations_reject_other_store_and_bad_input(): void
    {
        $other = DB::table('customers')->insertGetId(['manager_id' => $this->otherManager, 'name' => '他客', 'name_kana' => 'ﾀ'] + $this->t());
        $otherInvoice = DB::table('invoices')->insertGetId(['manager_id' => $this->otherManager, 'customer_id' => $other, 'period_start' => '2026-09-01', 'period_end' => '2026-09-30'] + $this->t());

        $this->actingAs($this->store)->postJson('/api/invoice/carry_over', ['invoices' => [$otherInvoice]])->assertNotFound();
        $this->actingAs($this->store)->postJson('/api/invoice/align_cutoff_date', ['invoices' => [$otherInvoice]])->assertNotFound();
        $this->actingAs($this->store)->postJson('/api/invoice/carry_over', ['invoices' => ['x']])->assertStatus(422);
        $this->assertNull(DB::table('invoices')->where('id', $otherInvoice)->value('carry_over_id'));
        $this->assertNull(DB::table('invoices')->where('id', $otherInvoice)->value('deleted_at'));
    }

    // ---- 入金確認 ------------------------------------------------------------------

    public function test_payment_confirmation_page_lists_unconfirmed_invoices_of_customer(): void
    {
        $open = $this->invoice($this->confirmCustomer, '2026-09-21', '2026-10-20');
        $this->order($open, $this->confirmCustomer, [[$this->shirt, '1-001']]);
        $this->invoice($this->confirmCustomer, '2026-08-21', '2026-09-20', ['paid_at' => '2026-09-20']);

        $res = $this->actingAs($this->store)->get('/invoice/payment_confimation?m='.$this->manager.'&c='.$this->confirmCustomer);

        $res->assertOk()->assertSee('入　金　確　認')->assertSee('payment-confirmation-component', false);
        $invoices = $res->viewData('invoices');
        $this->assertSame([$open], array_column($invoices, 'id'));
        $this->assertSame(220, (int) $invoices[0]['amount']);
        $this->assertCount(1, $invoices[0]['orders']);
        $this->assertSame('1-001', $invoices[0]['orders'][0]['items'][0]['tag']);

        $this->actingAs($this->store)->get('/invoice/payment_confimation?m='.$this->manager)->assertRedirect(route('home', ['m' => $this->manager]));
    }

    public function test_payment_confirmation_marks_the_invoice_not_an_order_with_the_same_id(): void
    {
        // 旧は請求書 ID を order_id として /api/payment に送っていた (同じ ID の別の注文が入金済みになる)
        $invoice = $this->invoice($this->confirmCustomer, '2026-09-21', '2026-10-20', ['id' => 9000]);
        $sameId = DB::table('orders')->insertGetId(['id' => 9000, 'store_id' => $this->store->id, 'manager_id' => $this->manager, 'customer_id' => $this->customer, 'amount' => 500, 'reduction' => 0, 'payment' => 0] + $this->t());
        $this->order($invoice, $this->confirmCustomer, [[$this->shirt, '1-001']]);

        $this->actingAs($this->store)->postJson('/api/invoice/payment_confirmation', ['invoice_id' => $invoice, 'payment' => 220])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('2026-10-23', DB::table('invoices')->where('id', $invoice)->value('paid_at'));
        $this->assertNull(DB::table('orders')->where('id', $sameId)->value('paid_at'));
        $this->assertSame(0, (int) DB::table('orders')->where('id', $sameId)->value('payment'));
    }

    // ---- 日報 ------------------------------------------------------------------------

    public function test_daily_report_index_and_pdf(): void
    {
        $id = $this->invoice($this->customer, '2026-10-01', '2026-10-31');
        $today = $this->order($id, $this->customer, [[$this->shirt, '1-001'], [$this->blanket, '1-002']], over: ['created_at' => '2026-10-23 09:30:00', 'paid_at' => '2026-10-23 09:30:00', 'reduction' => 100, 'discount' => 0, 'amount' => 1320]);
        $this->order($id, $this->customer, [[$this->shirt, '1-003']], over: ['created_at' => '2026-10-02 09:30:00']);

        $res = $this->actingAs($this->store)->get('/daily_report?m='.$this->manager);
        $res->assertOk()->assertSee('本日の日報')->assertSee('1,320 円')->assertSee('1,540 円')->assertSee('値引き金額');
        $this->assertSame([$today], array_column($res->viewData('daily_orders'), 'id'));
        $this->actingAs($this->store)->get('/daily_report?m='.$this->manager.'&date=2026-10-02')->assertOk()->assertSee('2026年10月02日の日報');

        $data = $this->captureView('daily_report.pdf');
        $pdf = $this->actingAs($this->store)->get('/daily-report/2026-10-23/pdf?m='.$this->manager);
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertSame([[$today, '1-001', 'ワイシャツ', 220], [$today, '1-002', '毛布', 1200]],
            array_map(fn ($r) => [$r['order_id'], $r['tag'], $r['name'], (int) $r['price']], $data['daily_orders']));
        $this->assertSame(1320, (int) $data['daily_sum']);
        $this->assertSame(1540, (int) $data['monthly_sum']);

        $this->actingAs($this->store)->get('/daily-report/2026-13-45/pdf?m='.$this->manager)->assertNotFound();
        $this->actingAs($this->store)->get('/daily_report')->assertRedirect(route('home'));
    }
}
