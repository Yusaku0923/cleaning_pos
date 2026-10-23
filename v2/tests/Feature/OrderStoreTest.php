<?php

namespace Tests\Feature;

use App\Domain\Tag\TagAllocator;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * 注文の登録 (旧 Api\OrdersController::store) とレシート用データ (旧 fetchReceiptInfo / Order::fetchReciptDetail)。
 * 金額はサーバーで計算し、送られた合計は使わない。注文・品物・タグ採番は 1 トランザクション。
 */
class OrderStoreTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private int $manager;

    private int $customer;

    private int $invoiceCustomer;

    private int $shirt;   // 500 円・タグ 1

    private int $suit;    // 1,800 円・タグ 3 (2 枚目以降は 999 の行)

    private int $noTag;   // 300 円・タグ 0 (旧と同じく 1 枚振る)

    private int $otherStoreClothes;

    private int $otherStoreCustomer;

    private function t(): array
    {
        return ['created_at' => now(), 'updated_at' => now()];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Date::setTestNow('2026-10-23 10:15:00');

        $storeId = DB::table('stores')->insertGetId(['name' => 'あさひ屋', 'email' => 'a@example.com', 'password' => 'x', 'address' => '日田市', 'phone_number' => '22-3540'] + $this->t());
        $otherId = DB::table('stores')->insertGetId(['name' => '他店', 'email' => 'b@example.com', 'password' => 'x', 'address' => '', 'phone_number' => ''] + $this->t());
        $this->store = Store::findOrFail($storeId);

        $this->manager = DB::table('managers')->insertGetId(['store_id' => $storeId, 'name' => '若宮本店'] + $this->t());
        $otherManager = DB::table('managers')->insertGetId(['store_id' => $otherId, 'name' => '他'] + $this->t());
        DB::table('tag_counters')->insert(['manager_id' => $this->manager, 'value' => 3592] + $this->t());

        $this->customer = DB::table('customers')->insertGetId(['manager_id' => $this->manager, 'name' => '山田', 'name_kana' => 'ﾔﾏﾀﾞ', 'phone_number' => '090', 'cutoff_date' => 99, 'needs_return_confirmation' => false] + $this->t());
        $this->invoiceCustomer = DB::table('customers')->insertGetId(['manager_id' => $this->manager, 'name' => '病院', 'name_kana' => 'ﾋﾞｮｳｲﾝ', 'cutoff_date' => 20, 'is_invoice' => true, 'needs_payment_confirmation' => true, 'needs_return_confirmation' => false] + $this->t());
        $this->otherStoreCustomer = DB::table('customers')->insertGetId(['manager_id' => $otherManager, 'name' => '他客', 'name_kana' => 'ﾀ'] + $this->t());

        $cat = DB::table('categories')->insertGetId(['store_id' => $storeId, 'name' => 'カテゴリ'] + $this->t());
        $otherCat = DB::table('categories')->insertGetId(['store_id' => $otherId, 'name' => '他'] + $this->t());
        $multiCat = DB::table('categories')->insertGetId(['id' => 999, 'store_id' => $storeId, 'name' => '〃'] + $this->t());
        // 旧と同じく 2 枚目以降のタグ行は clothes_id 999 (本番では論理削除済み)
        DB::table('clothes')->insert(['id' => 999, 'store_id' => $storeId, 'category_id' => $multiCat, 'name' => '〃', 'name_kana' => '', 'price' => 0, 'tag_count' => 1, 'deleted_at' => now()] + $this->t());
        $this->shirt = DB::table('clothes')->insertGetId(['store_id' => $storeId, 'category_id' => $cat, 'name' => 'ワイシャツ', 'name_kana' => 'ﾜｲｼｬﾂ', 'price' => 500, 'tag_count' => 1] + $this->t());
        $this->suit = DB::table('clothes')->insertGetId(['store_id' => $storeId, 'category_id' => $cat, 'name' => 'スリーピース', 'name_kana' => 'ｽﾘｰﾋﾟｰｽ', 'price' => 1800, 'tag_count' => 3] + $this->t());
        $this->noTag = DB::table('clothes')->insertGetId(['store_id' => $storeId, 'category_id' => $cat, 'name' => 'ネクタイ', 'name_kana' => 'ﾈｸﾀｲ', 'price' => 300, 'tag_count' => 0] + $this->t());
        $this->otherStoreClothes = DB::table('clothes')->insertGetId(['store_id' => $otherId, 'category_id' => $otherCat, 'name' => '他店の服', 'name_kana' => 'ﾀ', 'price' => 1, 'tag_count' => 1] + $this->t());

        DB::table('taxes')->insert(['store_id' => $storeId, 'tax' => '10'] + $this->t());
        Setting::set('printer_ip', '192.168.0.214');
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'manager_id' => $this->manager,
            'customer_id' => $this->customer,
            'items' => [
                ['clothes_id' => $this->shirt, 'qty' => 2],
                ['clothes_id' => $this->suit, 'qty' => 1],
            ],
            'discount_ratio' => null,
            'discount_amount' => null,
            'deposit' => 3000,
            'is_invoice' => false,
            'not_paid' => false,
            'check_return' => false,
            'created_at' => null,
        ], $over);
    }

    private function counter(): int
    {
        return (int) DB::table('tag_counters')->where('manager_id', $this->manager)->value('value');
    }

    private function snapshot(): array
    {
        return [
            DB::table('orders')->count(),
            DB::table('order_items')->count(),
            $this->counter(),
            DB::table('customers')->where('id', $this->customer)->value('total_sales'),
            DB::table('invoices')->count(),
        ];
    }

    public function test_store_creates_order_items_and_tags_in_one_transaction(): void
    {
        $res = $this->actingAs($this->store)->postJson('/api/orders', $this->payload())->assertCreated();

        // シャツ 2 枚 (3-592, 3-593) → スーツ 1 着 = タグ 3 枚 (3-594 本体, 3-595/3-596 は 999)
        $res->assertJson(['tags' => ['3-592', '3-593', '3-594', '3-595', '3-596'], 'total' => 2800]);
        $orderId = $res->json('order_id');

        $order = DB::table('orders')->find($orderId);
        $this->assertSame(2800, (int) $order->amount);
        $this->assertSame(0, (int) $order->reduction);
        $this->assertSame(0, (int) $order->discount);
        $this->assertSame(3000, (int) $order->payment);
        $this->assertSame($this->store->id, (int) $order->store_id);
        $this->assertSame('2026-10-23 10:15:00', $order->paid_at);
        $this->assertSame('2026-10-23 10:15:00', $order->handed_at); // 返却確認しない → 受付時にお渡し済み
        $this->assertNull($order->invoice_id);

        $items = DB::table('order_items')->where('order_id', $orderId)->orderBy('id')->get(['clothes_id', 'tag', 'handed_at']);
        $this->assertSame(
            [[$this->shirt, '3-592'], [$this->shirt, '3-593'], [$this->suit, '3-594'], [999, '3-595'], [999, '3-596']],
            $items->map(fn ($i) => [(int) $i->clothes_id, $i->tag])->all(),
        );
        $this->assertSame(3597, $this->counter());

        $c = DB::table('customers')->find($this->customer);
        $this->assertSame(2800, (int) $c->total_sales);
        $this->assertSame(1, (int) $c->number_of_visits);
    }

    public function test_failure_midway_leaves_nothing(): void
    {
        // タグを振った (tag_counters を進めた) あとで落ちる
        $this->app->bind(TagAllocator::class, fn () => new class extends TagAllocator
        {
            public function allocate(int $managerId, int $count): array
            {
                parent::allocate($managerId, $count);
                throw new RuntimeException('途中で失敗');
            }
        });
        $this->withoutExceptionHandling();
        $before = $this->snapshot();

        try {
            $this->actingAs($this->store)->postJson('/api/orders', $this->payload());
            $this->fail('例外が出ていない');
        } catch (RuntimeException $e) {
            $this->assertSame('途中で失敗', $e->getMessage());
        }

        $this->assertSame($before, $this->snapshot());
    }

    public function test_client_total_is_ignored(): void
    {
        $res = $this->actingAs($this->store)->postJson('/api/orders', $this->payload([
            'total' => 1, 'amount' => 1,
            'items' => [['clothes_id' => $this->shirt, 'qty' => 3, 'price' => 1]],
        ]))->assertCreated();

        $res->assertJson(['total' => 1500]);
        $this->assertSame(1500, (int) DB::table('orders')->where('id', $res->json('order_id'))->value('amount'));
    }

    public function test_invalid_clothes_id_is_422(): void
    {
        $this->actingAs($this->store);
        $before = $this->snapshot();

        $this->postJson('/api/orders', $this->payload(['items' => [['clothes_id' => 987654, 'qty' => 1]]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.clothes_id');
        // 他店舗の商品も見えない
        $this->postJson('/api/orders', $this->payload(['items' => [['clothes_id' => $this->shirt, 'qty' => 1], ['clothes_id' => $this->otherStoreClothes, 'qty' => 1]]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.1.clothes_id');

        $this->assertSame($before, $this->snapshot());
    }

    public function test_other_store_customer_or_manager_is_422(): void
    {
        $this->actingAs($this->store);
        $this->postJson('/api/orders', $this->payload(['customer_id' => $this->otherStoreCustomer]))
            ->assertStatus(422)->assertJsonValidationErrors('customer_id');
        $this->postJson('/api/orders', $this->payload(['manager_id' => 987654]))
            ->assertStatus(422)->assertJsonValidationErrors('manager_id');
        $this->postJson('/api/orders', $this->payload(['store_id' => 999]))->assertCreated();
        $this->assertSame([$this->store->id], DB::table('orders')->pluck('store_id')->map(fn ($v) => (int) $v)->all());
    }

    public function test_guest_is_401(): void
    {
        $this->postJson('/api/orders', $this->payload())->assertUnauthorized();
        $this->getJson('/api/receipt/1')->assertUnauthorized();
    }

    public function test_discount_ratio_and_amount(): void
    {
        $this->actingAs($this->store);
        // 割引 15%: 2,800 → 値引 floor(420) → 2,380。旧と同じく orders.discount に割合、reduction に金額
        $id = $this->postJson('/api/orders', $this->payload(['discount_ratio' => 15]))->assertCreated()->assertJson(['total' => 2380])->json('order_id');
        $o = DB::table('orders')->find($id);
        $this->assertSame([2380, 420, 15], [(int) $o->amount, (int) $o->reduction, (int) $o->discount]);

        // 値引 333 円
        $id = $this->postJson('/api/orders', $this->payload(['discount_amount' => 333]))->assertCreated()->assertJson(['total' => 2467])->json('order_id');
        $o = DB::table('orders')->find($id);
        $this->assertSame([2467, 333, 0], [(int) $o->amount, (int) $o->reduction, (int) $o->discount]);

        // 合計を超える値引は 422
        $this->postJson('/api/orders', $this->payload(['discount_amount' => 2801]))->assertStatus(422);
    }

    public function test_cash_deposit_must_cover_total(): void
    {
        $this->actingAs($this->store)->postJson('/api/orders', $this->payload(['deposit' => 2799]))
            ->assertStatus(422)->assertJsonValidationErrors('deposit');
    }

    public function test_not_paid_and_check_return_and_no_tag(): void
    {
        $res = $this->actingAs($this->store)->postJson('/api/orders', $this->payload([
            'items' => [
                ['clothes_id' => $this->shirt, 'qty' => 1, 'issue_tag' => false],
                ['clothes_id' => $this->suit, 'qty' => 1, 'issue_tag' => false],
                ['clothes_id' => $this->noTag, 'qty' => 2],
            ],
            'deposit' => 0, 'not_paid' => true, 'check_return' => true, 'created_at' => '2026-10-20',
        ]))->assertCreated();

        // タグを発行しない品物は 0-000 (999 の行も)。タグ 0 枚の商品も旧と同じく 1 点 1 枚
        $res->assertJson(['tags' => ['3-592', '3-593'], 'total' => 2900]);
        $id = $res->json('order_id');
        $this->assertSame(
            ['0-000', '0-000', '0-000', '0-000', '3-592', '3-593'],
            DB::table('order_items')->where('order_id', $id)->orderBy('id')->pluck('tag')->all(),
        );
        $this->assertSame(0, DB::table('order_items')->where('order_id', $id)->whereNotNull('handed_at')->count());
        $o = DB::table('orders')->find($id);
        $this->assertNull($o->paid_at);
        $this->assertNull($o->handed_at);
        $this->assertSame('2026-10-20 00:00:00', $o->created_at);
        $this->assertSame(3594, $this->counter());
    }

    public function test_invoice_order_joins_the_period_invoice(): void
    {
        $this->actingAs($this->store);
        $id = $this->postJson('/api/orders', $this->payload([
            'customer_id' => $this->invoiceCustomer, 'is_invoice' => true, 'deposit' => 0,
        ]))->assertCreated()->json('order_id');

        $o = DB::table('orders')->find($id);
        $inv = DB::table('invoices')->find($o->invoice_id);
        // 締め日 20・10/23 → 10/21〜11/20。入金確認ありの顧客は paid_at = 期間の終了日
        $this->assertSame(['2026-10-21', '2026-11-20', '2026-11-20'], [$inv->period_start, $inv->period_end, $inv->paid_at]);
        $this->assertSame('2026-11-20 00:00:00', $o->paid_at);
        $this->assertSame(1, (int) $o->is_invoice);

        // 同じ期間の 2 件目は同じ請求書
        $id2 = $this->postJson('/api/orders', $this->payload([
            'customer_id' => $this->invoiceCustomer, 'is_invoice' => true, 'deposit' => 0,
        ]))->assertCreated()->json('order_id');
        $this->assertSame($o->invoice_id, DB::table('orders')->where('id', $id2)->value('invoice_id'));

        // 請求書払いでない顧客に is_invoice は付けられない
        $this->postJson('/api/orders', $this->payload(['is_invoice' => true, 'deposit' => 0]))->assertStatus(422);
    }

    public function test_receipt_keeps_legacy_keys(): void
    {
        $this->actingAs($this->store);
        $id = $this->postJson('/api/orders', $this->payload(['discount_ratio' => 10]))->assertCreated()->json('order_id');

        $res = $this->getJson("/api/receipt/{$id}")->assertOk();
        $this->assertSame([
            'store_name' => 'あさひ屋',
            'store_address' => '日田市',
            'store_tel' => '22-3540',
            'customer_name_kana' => 'ﾔﾏﾀﾞ',
            'customer_name' => '山田',
            'customer_tel' => '090',
            'manager_name' => '若宮本店',
            'ordered_at' => '2026年10月23日 10時15分',
            'order_list' => [
                ['id' => $this->shirt, 'name' => 'ﾜｲｼｬﾂ', 'tag_start' => '3-592', 'tag_end' => '3-593', 'price' => 1000, 'count' => 2],
                ['id' => $this->suit, 'name' => 'ｽﾘｰﾋﾟｰｽ', 'tag_start' => '3-594', 'tag_end' => '3-596', 'price' => 1800, 'count' => 1],
            ],
            'total_count' => 5,
            'amount' => 2520,
            'discount' => 10,           // 旧と同じく orders.discount (割合)
            'discount_raito' => null,   // 旧の綴りのまま (旧 DB にカラムは無く常に null)
            'payment' => 3000,
            'tax' => '10',
            'paid_at' => '2026-10-23 10:15:00',
            'is_invoice' => 0,
            'ip_address' => '192.168.0.214',
        ], $res->json());
    }

    public function test_receipt_lines_for_multi_tag_items_and_wrap(): void
    {
        $this->actingAs($this->store);
        DB::table('tag_counters')->where('manager_id', $this->manager)->update(['value' => 10997]);

        // シャツ 3 枚 (10-997, 10-998, 10-999)、スーツ 2 着 (1-000〜1-002, 1-003〜1-005)
        $id = $this->postJson('/api/orders', $this->payload(['items' => [
            ['clothes_id' => $this->shirt, 'qty' => 3],
            ['clothes_id' => $this->suit, 'qty' => 2],
        ], 'deposit' => 5100]))->assertCreated()
            ->assertJson(['tags' => ['10-997', '10-998', '10-999', '1-000', '1-001', '1-002', '1-003', '1-004', '1-005']])
            ->json('order_id');
        $this->assertSame(1006, $this->counter());

        $list = $this->getJson("/api/receipt/{$id}")->assertOk()->json('order_list');
        // 旧の並び (前の数で比べる) のまま: 1-xxx が先。タグが続くスーツは 1 着ずつの行
        $this->assertSame([
            ['id' => $this->suit, 'name' => 'ｽﾘｰﾋﾟｰｽ', 'tag_start' => '1-000', 'tag_end' => '1-002', 'price' => 1800, 'count' => 1],
            ['id' => $this->suit, 'name' => 'ｽﾘｰﾋﾟｰｽ', 'tag_start' => '1-003', 'tag_end' => '1-005', 'price' => 1800, 'count' => 1],
            ['id' => $this->shirt, 'name' => 'ﾜｲｼｬﾂ', 'tag_start' => '10-997', 'tag_end' => '10-999', 'price' => 1500, 'count' => 3],
        ], $list);
    }

    public function test_receipt_of_other_store_is_404(): void
    {
        $t = $this->t();
        $otherStore = DB::table('stores')->where('email', 'b@example.com')->value('id');
        $otherManager = DB::table('managers')->where('store_id', $otherStore)->value('id');
        $id = DB::table('orders')->insertGetId(['store_id' => $otherStore, 'manager_id' => $otherManager, 'customer_id' => $this->otherStoreCustomer, 'amount' => 1, 'reduction' => 0, 'payment' => 1] + $t);

        $this->actingAs($this->store)->getJson("/api/receipt/{$id}")->assertNotFound();
    }

    public function test_order_create_page_needs_manager_and_customer(): void
    {
        $this->actingAs($this->store);
        $this->get('/order/create')->assertRedirect('/');
        $this->get("/order/create?m={$this->manager}")->assertRedirect("/?m={$this->manager}");

        $html = $this->get("/order/create?m={$this->manager}&c={$this->customer}")->assertOk()->getContent();
        $this->assertStringContainsString('<order-component', $html);
        $this->assertStringContainsString(':printer_ip="&quot;192.168.0.214&quot;"', $html);
        // 旧と同じく json_encode (\uXXXX) で埋め込む
        $this->assertStringContainsString(trim(json_encode('ワイシャツ'), '"'), $html);
        $this->assertStringNotContainsString(trim(json_encode('他店の服'), '"'), $html);
    }

    public function test_clothes_search_is_scoped(): void
    {
        $this->actingAs($this->store);
        $names = collect($this->postJson('/api/clothes/search', ['name' => '服'])->assertOk()->json('clothes'))->pluck('name');
        $this->assertCount(0, $names);
        $names = collect($this->postJson('/api/clothes/search', ['name' => 'シャツ'])->assertOk()->json('clothes'))->pluck('name');
        $this->assertSame(['ワイシャツ'], $names->all());
    }
}
