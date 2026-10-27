<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * お渡し (旧 Api\ReturnController)、入金 (旧 Api\OrdersController::payment)、預り一覧・履歴 (旧 search / show / all_show)、
 * 注文の取り消し (旧 destroy / Api delete)、タグ番号の変更 (旧 TagNumberController / Api updateTag)。
 */
class ReturnPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Store $other;

    private int $manager;

    private int $customer;

    private int $invoiceCustomer;

    private int $otherCustomer;

    private int $otherManager;

    private int $shirt;

    private function t(): array
    {
        return ['created_at' => now(), 'updated_at' => now()];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow('2026-10-23 10:15:00');

        $storeId = DB::table('stores')->insertGetId(['name' => 'あさひ屋', 'email' => 'a@example.com', 'password' => 'x', 'address' => '', 'phone_number' => ''] + $this->t());
        $otherId = DB::table('stores')->insertGetId(['name' => '他店', 'email' => 'b@example.com', 'password' => 'x', 'address' => '', 'phone_number' => ''] + $this->t());
        $this->store = Store::findOrFail($storeId);
        $this->other = Store::findOrFail($otherId);

        $this->manager = DB::table('managers')->insertGetId(['store_id' => $storeId, 'name' => '若宮本店'] + $this->t());
        $this->otherManager = DB::table('managers')->insertGetId(['store_id' => $otherId, 'name' => '他'] + $this->t());
        DB::table('tag_counters')->insert(['manager_id' => $this->manager, 'value' => 3592] + $this->t());
        DB::table('tag_counters')->insert(['manager_id' => $this->otherManager, 'value' => 5000] + $this->t());

        $this->customer = DB::table('customers')->insertGetId(['manager_id' => $this->manager, 'name' => '山田', 'name_kana' => 'ﾔﾏﾀﾞ', 'cutoff_date' => 99, 'total_sales' => 0, 'number_of_visits' => 0] + $this->t());
        $this->invoiceCustomer = DB::table('customers')->insertGetId(['manager_id' => $this->manager, 'name' => '病院', 'name_kana' => 'ﾋﾞｮｳｲﾝ', 'cutoff_date' => 20, 'is_invoice' => true, 'needs_payment_confirmation' => true, 'needs_return_confirmation' => true, 'total_sales' => 0, 'number_of_visits' => 0] + $this->t());
        $this->otherCustomer = DB::table('customers')->insertGetId(['manager_id' => $this->otherManager, 'name' => '他客', 'name_kana' => 'ﾀ'] + $this->t());

        $cat = DB::table('categories')->insertGetId(['store_id' => $storeId, 'name' => 'カテゴリ'] + $this->t());
        $this->shirt = DB::table('clothes')->insertGetId(['store_id' => $storeId, 'category_id' => $cat, 'name' => 'ワイシャツ', 'name_kana' => 'ﾜｲｼｬﾂ', 'price' => 500, 'tag_count' => 1] + $this->t());
    }

    private function storeOrder(int $customerId, array $over = []): int
    {
        $res = $this->actingAs($this->store)->postJson('/api/orders', array_merge([
            'manager_id' => $this->manager,
            'customer_id' => $customerId,
            'items' => [['clothes_id' => $this->shirt, 'qty' => 2]],
            'deposit' => 1000,
            'is_invoice' => false,
            'not_paid' => false,
            'check_return' => false,
        ], $over))->assertCreated();

        return $res->json('order_id');
    }

    /** 他店舗の注文 (DB に直接) */
    private function otherStoreOrder(): array
    {
        $order = DB::table('orders')->insertGetId(['store_id' => $this->other->id, 'manager_id' => $this->otherManager, 'customer_id' => $this->otherCustomer, 'amount' => 700, 'payment' => 0, 'reduction' => 0, 'discount' => 0] + $this->t());
        $item = DB::table('order_items')->insertGetId(['order_id' => $order, 'clothes_id' => $this->shirt, 'tag' => '5-000'] + $this->t());

        return [$order, $item];
    }

    private function items(int $orderId): array
    {
        return DB::table('order_items')->where('order_id', $orderId)->orderBy('id')->pluck('id')->all();
    }

    private function ctx(array $extra = []): string
    {
        return '?'.http_build_query(array_merge(['m' => $this->manager], $extra));
    }

    // --- お渡し ---

    public function test_return_creates_invoice_via_same_service_as_order(): void
    {
        // 注文経路: 返却確認なしの請求書払い → 注文日の期間の請求書 (入金確認ありなので paid_at = 期間の終了日)
        DB::table('customers')->where('id', $this->invoiceCustomer)->update(['needs_return_confirmation' => false]);
        $viaOrder = $this->storeOrder($this->invoiceCustomer, ['is_invoice' => true, 'not_paid' => true, 'deposit' => 0]);
        $orderInvoice = DB::table('invoices')->find(DB::table('orders')->where('id', $viaOrder)->value('invoice_id'));
        $this->assertNotNull($orderInvoice);

        // 返却経路: 返却確認ありの請求書払い (請求書はまだ無い) を同じ日に全部渡す
        $viaReturn = $this->storeOrder($this->invoiceCustomer, ['is_invoice' => true, 'not_paid' => true, 'deposit' => 0, 'check_return' => true]);
        $this->assertNull(DB::table('orders')->where('id', $viaReturn)->value('invoice_id'));
        $this->actingAs($this->store)->postJson('/api/return/update', ['items' => $this->items($viaReturn)])->assertOk()->assertJson(['ok' => true]);

        $returnInvoiceId = DB::table('orders')->where('id', $viaReturn)->value('invoice_id');
        $this->assertSame((int) $orderInvoice->id, (int) $returnInvoiceId, '同じ顧客・同じ日なら同じ請求書');
        $this->assertSame(1, DB::table('invoices')->count());
        $this->assertSame('2026-11-20', DB::table('invoices')->where('id', $returnInvoiceId)->value('paid_at'));
        $this->assertSame($orderInvoice->paid_at, DB::table('invoices')->where('id', $returnInvoiceId)->value('paid_at'));

        // 別の顧客 (入金確認なし) でも、注文経路と返却経路の paid_at は同じ (null)
        $plain = DB::table('customers')->insertGetId(['manager_id' => $this->manager, 'name' => '会社', 'name_kana' => 'ｶｲｼｬ', 'cutoff_date' => 99, 'is_invoice' => true, 'needs_payment_confirmation' => false, 'needs_return_confirmation' => true] + $this->t());
        $o = $this->storeOrder($plain, ['is_invoice' => true, 'not_paid' => true, 'deposit' => 0, 'check_return' => true]);
        $this->actingAs($this->store)->postJson('/api/return/update', ['items' => $this->items($o)])->assertOk();
        $inv = DB::table('invoices')->find(DB::table('orders')->where('id', $o)->value('invoice_id'));
        $this->assertSame(['2026-10-01', '2026-10-31', null], [$inv->period_start, $inv->period_end, $inv->paid_at]);
    }

    public function test_return_partial_then_all_with_date(): void
    {
        $order = $this->storeOrder($this->customer, ['check_return' => true]);
        [$a, $b] = $this->items($order);

        // 一部だけ: 品物だけ渡し済み、注文は未渡しのまま
        $this->actingAs($this->store)->postJson('/api/return/update', ['items' => [$a]])->assertOk();
        $this->assertSame('2026-10-23 10:15:00', DB::table('order_items')->where('id', $a)->value('handed_at'));
        $this->assertNull(DB::table('order_items')->where('id', $b)->value('handed_at'));
        $this->assertNull(DB::table('orders')->where('id', $order)->value('handed_at'));

        // 残りを渡す (日付指定あり → その日の 00:00。旧と同じ)
        $this->actingAs($this->store)->postJson('/api/return/update', ['items' => [$b], 'handed_at' => '2026-10-20'])->assertOk();
        $this->assertSame('2026-10-20 00:00:00', DB::table('orders')->where('id', $order)->value('handed_at'));
        $this->assertNull(DB::table('orders')->where('id', $order)->value('invoice_id'), '請求書払いでない注文に請求書は付けない');
    }

    public function test_return_rejects_other_store_and_mixed_orders(): void
    {
        [, $otherItem] = $this->otherStoreOrder();
        $this->actingAs($this->store)->postJson('/api/return/update', ['items' => [$otherItem]])->assertNotFound();
        $this->assertNull(DB::table('order_items')->where('id', $otherItem)->value('handed_at'));

        $o1 = $this->storeOrder($this->customer, ['check_return' => true]);
        $o2 = $this->storeOrder($this->customer, ['check_return' => true]);
        $this->actingAs($this->store)->postJson('/api/return/update', ['items' => [$this->items($o1)[0], $this->items($o2)[0]]])->assertStatus(422);
        $this->actingAs($this->store)->postJson('/api/return/update', ['items' => []])->assertStatus(422);
        $this->assertSame(0, DB::table('order_items')->whereNotNull('handed_at')->whereIn('order_id', [$o1, $o2])->count());
    }

    public function test_return_page_lists_unhanded_orders_of_selected_customer(): void
    {
        $handed = $this->storeOrder($this->customer);
        $unhanded = $this->storeOrder($this->customer, ['check_return' => true]);

        $res = $this->actingAs($this->store)->get('/return'.$this->ctx(['c' => $this->customer]))->assertOk();
        $orders = $res->viewData('orders');
        $this->assertSame([$unhanded], array_column($orders, 'id'));
        $this->assertSame('2026/10/23', $orders[0]['created_at']);
        $this->assertSame(['ワイシャツ', 'ワイシャツ'], array_column($orders[0]['items'], 'name'));
        $res->assertSee('<return-component', false)->assertDontSee(':token', false);
        $this->assertNotContains($handed, array_column($orders, 'id'));

        // 顧客を選んでいなければホームへ (旧と同じ)
        $this->actingAs($this->store)->get('/return'.$this->ctx())->assertRedirect('/?m='.$this->manager);
    }

    // --- 入金 ---

    public function test_payment_page_and_pay(): void
    {
        $paid = $this->storeOrder($this->customer);
        $unpaid = $this->storeOrder($this->customer, ['not_paid' => true, 'deposit' => 0]);

        $res = $this->actingAs($this->store)->get('/payment'.$this->ctx(['c' => $this->customer]))->assertOk();
        $this->assertSame([$unpaid], array_column($res->viewData('orders'), 'id'));
        $res->assertSee('<payment-component', false);

        Date::setTestNow('2026-10-25 09:00:00');
        $this->actingAs($this->store)->postJson('/api/payment', ['order_id' => $unpaid, 'payment' => 2000])->assertOk()->assertJson(['ok' => true]);
        $row = DB::table('orders')->find($unpaid);
        $this->assertSame([2000, '2026-10-25 09:00:00'], [(int) $row->payment, $row->paid_at]);
        $this->assertNotNull(DB::table('orders')->where('id', $paid)->value('paid_at'));

        $this->actingAs($this->store)->postJson('/api/payment', ['order_id' => $unpaid, 'payment' => 'abc'])->assertStatus(422);
        $this->actingAs($this->store)->get('/payment'.$this->ctx())->assertRedirect('/?m='.$this->manager);
    }

    public function test_payment_of_other_store_order_is_404(): void
    {
        [$order] = $this->otherStoreOrder();
        $this->actingAs($this->store)->postJson('/api/payment', ['order_id' => $order, 'payment' => 700])->assertNotFound();
        $this->assertNull(DB::table('orders')->where('id', $order)->value('paid_at'));
    }

    // --- 取り消し ---

    public function test_delete_order_is_post_or_delete_only(): void
    {
        $order = $this->storeOrder($this->customer);

        $this->actingAs($this->store)->get('/order/delete/'.$order)->assertStatus(405);
        $this->actingAs($this->store)->getJson('/api/order/delete?order_id='.$order)->assertStatus(405);
        $this->assertNotNull(DB::table('orders')->where('id', $order)->whereNull('deleted_at')->first(), 'GET では消えない');

        // 画面 (直前預り取り消し) は DELETE、履歴は POST
        $this->actingAs($this->store)->delete('/order/delete/'.$order.$this->ctx(['c' => $this->customer]))
            ->assertRedirect('/?m='.$this->manager.'&c='.$this->customer);
        $this->assertSoftDeleted('orders', ['id' => $order]);

        $order2 = $this->storeOrder($this->customer);
        $this->actingAs($this->store)->postJson('/api/order/delete', ['order_id' => $order2, 'customer_id' => $this->customer])->assertOk()->assertJson(['ok' => true]);
        $this->assertSoftDeleted('orders', ['id' => $order2]);
    }

    public function test_delete_order_undoes_items_and_customer_totals(): void
    {
        $order = $this->storeOrder($this->customer);
        $this->assertSame([1000, 1], [(int) DB::table('customers')->where('id', $this->customer)->value('total_sales'), (int) DB::table('customers')->where('id', $this->customer)->value('number_of_visits')]);

        // 全体検索からの取り消しは customer_id が null (旧はこのとき集計を戻さなかった)。v2 は注文の顧客で戻す
        $this->actingAs($this->store)->postJson('/api/order/delete', ['order_id' => $order, 'customer_id' => null])->assertOk();
        $this->assertSame(0, DB::table('order_items')->where('order_id', $order)->whereNull('deleted_at')->count());
        $this->assertSame([0, 0], [(int) DB::table('customers')->where('id', $this->customer)->value('total_sales'), (int) DB::table('customers')->where('id', $this->customer)->value('number_of_visits')]);
    }

    public function test_delete_order_with_invoice_cleans_up_in_one_transaction(): void
    {
        DB::table('customers')->where('id', $this->invoiceCustomer)->update(['needs_return_confirmation' => false]);
        $first = $this->storeOrder($this->invoiceCustomer, ['is_invoice' => true, 'not_paid' => true, 'deposit' => 0]);
        $second = $this->storeOrder($this->invoiceCustomer, ['is_invoice' => true, 'not_paid' => true, 'deposit' => 0]);
        $invoice = DB::table('orders')->where('id', $first)->value('invoice_id');
        $this->assertSame($invoice, DB::table('orders')->where('id', $second)->value('invoice_id'));

        // 他の注文が残る請求書は残す
        $this->actingAs($this->store)->postJson('/api/order/delete', ['order_id' => $first, 'customer_id' => $this->invoiceCustomer])->assertOk();
        $this->assertNotSoftDeleted('invoices', ['id' => $invoice]);

        // 最後の注文を消すと、空になった未発行の請求書も消える (旧の意図。旧はここで 500 になり請求書が残った)
        $this->actingAs($this->store)->postJson('/api/order/delete', ['order_id' => $second, 'customer_id' => $this->invoiceCustomer])->assertOk()->assertJson(['ok' => true]);
        $this->assertSoftDeleted('orders', ['id' => $second]);
        $this->assertSoftDeleted('invoices', ['id' => $invoice]);
    }

    public function test_delete_keeps_issued_or_carried_over_invoice(): void
    {
        DB::table('customers')->where('id', $this->invoiceCustomer)->update(['needs_return_confirmation' => false]);

        $issued = $this->storeOrder($this->invoiceCustomer, ['is_invoice' => true, 'not_paid' => true, 'deposit' => 0]);
        $issuedInvoice = DB::table('orders')->where('id', $issued)->value('invoice_id');
        DB::table('invoices')->where('id', $issuedInvoice)->update(['issued_at' => '2026-10-23']);
        $this->actingAs($this->store)->postJson('/api/order/delete', ['order_id' => $issued])->assertOk();
        $this->assertSoftDeleted('orders', ['id' => $issued]);
        $this->assertNotSoftDeleted('invoices', ['id' => $issuedInvoice]); // 発行済みの請求書は空でも残す

        // 繰越の元・先になっている請求書も残す (繰越額の計算が壊れるため)
        Date::setTestNow('2026-11-25 10:00:00');
        $carried = $this->storeOrder($this->invoiceCustomer, ['is_invoice' => true, 'not_paid' => true, 'deposit' => 0]);
        $carriedInvoice = DB::table('orders')->where('id', $carried)->value('invoice_id');
        $this->assertNotSame($issuedInvoice, $carriedInvoice);
        DB::table('invoices')->where('id', $issuedInvoice)->update(['carry_over_id' => $carriedInvoice]);
        DB::table('invoices')->where('id', $carriedInvoice)->update(['has_carried_over' => true]);
        $this->actingAs($this->store)->postJson('/api/order/delete', ['order_id' => $carried])->assertOk();
        $this->assertNotSoftDeleted('invoices', ['id' => $carriedInvoice]);
    }

    public function test_delete_of_other_store_order_is_404(): void
    {
        [$order] = $this->otherStoreOrder();
        $this->actingAs($this->store)->postJson('/api/order/delete', ['order_id' => $order])->assertNotFound();
        $this->actingAs($this->store)->delete('/order/delete/'.$order.$this->ctx())->assertNotFound();
        $this->assertNotSoftDeleted('orders', ['id' => $order]);
    }

    // --- タグ ---

    public function test_tag_update_saves_head_times_1000_plus_body(): void
    {
        $this->actingAs($this->store)->post('/tag/update'.$this->ctx(['c' => $this->customer]), ['tag_head' => 8, 'tag_body' => 73])
            ->assertRedirect('/?m='.$this->manager.'&c='.$this->customer);
        $this->assertSame(8073, (int) DB::table('tag_counters')->where('manager_id', $this->manager)->value('value'));

        $this->actingAs($this->store)->post('/tag/update'.$this->ctx(), ['tag_head' => 10, 'tag_body' => 999]);
        $this->assertSame(10999, (int) DB::table('tag_counters')->where('manager_id', $this->manager)->value('value'));

        // 範囲外は変えない
        $this->actingAs($this->store)->post('/tag/update'.$this->ctx(), ['tag_head' => 11, 'tag_body' => 0])->assertRedirect();
        $this->actingAs($this->store)->post('/tag/update'.$this->ctx(), ['tag_head' => 3, 'tag_body' => ''])->assertRedirect();
        $this->assertSame(10999, (int) DB::table('tag_counters')->where('manager_id', $this->manager)->value('value'));

        // 他店舗の担当者 (m) は 404、変わらない。GET は 405
        $this->actingAs($this->store)->post('/tag/update?m='.$this->otherManager, ['tag_head' => 1, 'tag_body' => 0])->assertNotFound();
        $this->assertSame(5000, (int) DB::table('tag_counters')->where('manager_id', $this->otherManager)->value('value'));
        $this->actingAs($this->store)->get('/tag/update'.$this->ctx())->assertStatus(405);
    }

    public function test_tag_update_reaches_soft_deleted_counter_shown_on_home(): void
    {
        DB::table('tag_counters')->where('manager_id', $this->manager)->update(['deleted_at' => now()]);
        $this->actingAs($this->store)->get('/'.$this->ctx())->assertOk()->assertSee('タグ番号<br>3-592', false);

        $this->actingAs($this->store)->post('/tag/update'.$this->ctx(), ['tag_head' => 4, 'tag_body' => 1]);
        $this->assertSame(4001, (int) DB::table('tag_counters')->where('manager_id', $this->manager)->value('value'));
    }

    public function test_update_item_tag(): void
    {
        $order = $this->storeOrder($this->customer);
        [$a] = $this->items($order);
        $this->actingAs($this->store)->postJson('/api/order/update_tag', ['id' => $a, 'tag' => '9-001'])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('9-001', DB::table('order_items')->where('id', $a)->value('tag'));

        $this->actingAs($this->store)->postJson('/api/order/update_tag', ['id' => $a, 'tag' => '1--12'])->assertStatus(422);
        $this->assertSame('9-001', DB::table('order_items')->where('id', $a)->value('tag'));

        [, $otherItem] = $this->otherStoreOrder();
        $this->actingAs($this->store)->postJson('/api/order/update_tag', ['id' => $otherItem, 'tag' => '1-000'])->assertNotFound();
        $this->assertSame('5-000', DB::table('order_items')->where('id', $otherItem)->value('tag'));
    }

    // --- 預り一覧・全体検索 ---

    public function test_show_page_and_search(): void
    {
        Date::setTestNow('2026-10-01 09:00:00');
        $old = $this->storeOrder($this->customer, ['not_paid' => true, 'deposit' => 0]);
        Date::setTestNow('2026-10-23 10:15:00');
        $new = $this->storeOrder($this->customer, ['check_return' => true]);
        $this->otherStoreOrder();

        $res = $this->actingAs($this->store)->get('/order/show'.$this->ctx(['c' => $this->customer]))->assertOk();
        $orders = $res->viewData('orders');
        $this->assertSame([$new, $old], array_column($orders, 'id'));
        $this->assertSame(['山田', 2], [$orders[0]['customer_name'], $orders[0]['count']]);
        $this->assertSame(['id', 'order_id', 'clothes_id', 'tag', 'handed_at', 'name', 'name_kana', 'price', 'tag_count'], array_values(array_intersect(['id', 'order_id', 'clothes_id', 'tag', 'handed_at', 'name', 'name_kana', 'price', 'tag_count'], array_keys($orders[0]['items'][0]))));
        $res->assertSee('<history-component', false);
        // 担当者・顧客が揃っていなければホームへ
        $this->actingAs($this->store)->get('/order/show'.$this->ctx())->assertRedirect('/?m='.$this->manager);

        $search = fn (array $conditions, $customer) => $this->actingAs($this->store)->postJson('/api/order/search', ['customer_id' => $customer, 'conditions' => $conditions])->assertOk();

        $all = ['order_id' => '', 'after' => '', 'before' => '', 'tag' => '', 'has_paid' => 'neither', 'has_handed' => 'neither'];
        $search($all, $this->customer)->assertJson(['orders' => [['id' => $new], ['id' => $old]], 'conditions' => []]);
        $search(['has_paid' => 'unpaid'] + $all, $this->customer)->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $old)->assertJsonPath('conditions', ['お支払い済み' => 'いいえ']);
        $search(['has_handed' => 'handed'] + $all, $this->customer)->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $old)->assertJsonPath('conditions', ['お渡し済み' => 'はい']);
        $search(['after' => '2026-10-10'] + $all, $this->customer)->assertJsonCount(1, 'orders')->assertJsonPath('conditions', ['期間' => '2026/10/10 ～ なし']);
        $search(['before' => '2026-10-01'] + $all, $this->customer)->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $old)->assertJsonPath('conditions', ['期間' => 'なし ～ 2026/10/01']);
        $search(['order_id' => (string) $new] + $all, $this->customer)->assertJsonCount(1, 'orders')->assertJsonPath('conditions', ['伝票番号' => (string) $new]);
        $tag = DB::table('order_items')->where('order_id', $old)->value('tag');
        $search(['tag' => $tag] + $all, $this->customer)->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $old)->assertJsonPath('conditions', ['タグ' => $tag]);
        $search(['tag' => '1-234'] + $all, $this->customer)->assertJsonCount(0, 'orders');

        // 全体検索 (customer_id null) は自店舗の注文だけ
        $search($all, null)->assertJsonCount(2, 'orders');
        $this->actingAs($this->store)->get('/order/all_show')->assertOk()->assertSee('<all-history-component', false);
        // 他店舗の顧客 ID を指定しても何も出ない
        $search($all, $this->otherCustomer)->assertJsonCount(0, 'orders');
    }

    public function test_guest_is_rejected(): void
    {
        $this->postJson('/api/order/delete', ['order_id' => 1])->assertUnauthorized();
        $this->postJson('/api/payment', ['order_id' => 1])->assertUnauthorized();
        $this->postJson('/api/return/update', ['items' => [1]])->assertUnauthorized();
        $this->get('/return')->assertRedirect('/login');
    }
}
