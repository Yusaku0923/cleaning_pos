<?php

namespace Tests\Feature;

use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryNote;
use App\Models\DeliveryProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Tests\Support\DeliveryFixtures;
use Tests\TestCase;

/** 旧 tests/Feature/DeliveryEntryApiTest の移植 (日次入力 API)。v2 は Sanctum の SPA 方式 (セッション Cookie)。 */
class DeliveryEntryApiTest extends TestCase
{
    use DeliveryFixtures;
    use RefreshDatabase;

    public function test_requires_login(): void
    {
        $this->getJson('/api/delivery/customers')->assertUnauthorized();
    }

    public function test_can_fetch_customers_with_departments(): void
    {
        $store = $this->makeStore();
        $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->getJson('/api/delivery/customers')
            ->assertOk()
            ->assertJsonStructure(['customers' => [['id', 'name', 'departments' => [['id', 'name', 'products' => [['id', 'name']]]]]]]);
    }

    public function test_other_stores_data_is_hidden(): void
    {
        $mine = $this->makeStore();
        $other = $this->makeStore('o@example.com');
        $this->makeDeliveryCustomer($mine, '自店');
        [$theirs, , $theirProduct] = $this->makeDeliveryCustomer($other, '他店');

        $this->actingAs($mine)->getJson('/api/delivery/customers')->assertOk()->assertJsonCount(1, 'customers');
        $this->actingAs($mine)->getJson("/api/delivery/customers/{$theirs->id}/departments")->assertNotFound();
        $this->actingAs($mine)->getJson("/api/delivery/entries?customer_id={$theirs->id}&date=2026-03-19")->assertStatus(422);
        $this->actingAs($mine)->postJson('/api/delivery/entries', [
            'entries' => [['delivery_product_id' => $theirProduct->id, 'date' => '2026-03-19', 'quantity' => 3]],
        ])->assertStatus(422);
        $this->assertSame(0, DeliveryDailyEntry::withoutGlobalScopes()->count());
    }

    public function test_can_upsert_entry(): void
    {
        $store = $this->makeStore();
        [, , $product] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->postJson('/api/delivery/entries', [
            'entries' => [['delivery_product_id' => $product->id, 'date' => '2026-03-19', 'quantity' => 10]],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('delivery_daily_entries', ['delivery_product_id' => $product->id, 'date' => '2026-03-19', 'quantity' => 10]);
    }

    public function test_upsert_updates_existing_entry(): void
    {
        $store = $this->makeStore();
        [, , $product] = $this->makeDeliveryCustomer($store);
        $this->makeEntry($product, '2026-03-20', 5);

        $this->actingAs($store)->postJson('/api/delivery/entries', [
            'entries' => [['delivery_product_id' => $product->id, 'date' => '2026-03-20', 'quantity' => 15, 'note' => 'メモ']],
        ])->assertOk();

        $this->assertDatabaseHas('delivery_daily_entries', ['delivery_product_id' => $product->id, 'date' => '2026-03-20', 'quantity' => 15, 'note' => 'メモ']);
        $this->assertSame(1, DeliveryDailyEntry::where('delivery_product_id', $product->id)->where('date', '2026-03-20')->count());
    }

    public function test_store_validates_entries(): void
    {
        $store = $this->makeStore();
        [, , $product] = $this->makeDeliveryCustomer($store);

        foreach ([
            [],
            ['entries' => [['delivery_product_id' => 999999, 'date' => '2026-03-19', 'quantity' => 1]]],
            ['entries' => [['delivery_product_id' => $product->id, 'date' => 'x', 'quantity' => 1]]],
            ['entries' => [['delivery_product_id' => $product->id, 'date' => '2026-03-19', 'quantity' => -1]]],
            ['entries' => [['delivery_product_id' => $product->id, 'date' => '2026-03-19', 'quantity' => 1.5]]],
        ] as $body) {
            $this->actingAs($store)->postJson('/api/delivery/entries', $body)->assertStatus(422);
        }
        $this->assertSame(0, DeliveryDailyEntry::count());
    }

    public function test_can_fetch_entries_by_customer_and_date(): void
    {
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store);
        $this->makeEntry($product, '2026-03-19', 4);
        $this->makeEntry($product, '2026-03-20', 9);

        $this->actingAs($store)->getJson("/api/delivery/entries?customer_id={$customer->id}&date=2026-03-19")
            ->assertOk()
            ->assertJsonCount(1, 'entries')
            ->assertJsonPath('entries.0.quantity', 4)
            ->assertJsonPath('entries.0.product.department.name', '総務部');
    }

    public function test_entry_status_lists_recent_dates_with_finalized_flag(): void
    {
        Date::setTestNow('2026-03-25 10:00:00');
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store);
        $this->makeEntry($product, '2026-03-19', 4);
        $this->makeEntry($product, '2026-03-20', 0);          // 数量 0 は入力済みに数えない
        $this->makeEntry($product, '2025-12-01', 4);          // 60 日より前
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-19');
        DeliveryDailyEntry::where('date', '2026-03-19')->update(['delivery_note_id' => $note->id]);
        $this->makeEntry($product, '2026-03-22', 2);

        $res = $this->actingAs($store)->getJson("/api/delivery/entry-status?customer_id={$customer->id}")->assertOk();
        $this->assertEquals(
            [['date' => '2026-03-19', 'finalized' => true], ['date' => '2026-03-22', 'finalized' => false]],
            collect($res->json('dates'))->sortBy('date')->values()->all()
        );
    }

    public function test_destroy_deletes_the_days_entries_of_the_customer_only(): void
    {
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store);
        [, , $otherProduct] = $this->makeDeliveryCustomer($store, '別の会社');
        $this->makeEntry($product, '2026-03-19', 4);
        $this->makeEntry($product, '2026-03-20', 4);
        $this->makeEntry($otherProduct, '2026-03-19', 7);

        $this->actingAs($store)->deleteJson('/api/delivery/entries', ['customer_id' => $customer->id, 'date' => '2026-03-19'])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(2, DeliveryDailyEntry::count());
        $this->assertDatabaseHas('delivery_daily_entries', ['delivery_product_id' => $otherProduct->id, 'date' => '2026-03-19']);
    }

    public function test_preview_aggregates_with_floor_tax_per_rate(): void
    {
        $store = $this->makeStore();
        [$customer, $dept, $product] = $this->makeDeliveryCustomer($store, price: 155);   // 10%
        $reduced = DeliveryProduct::create(['delivery_department_id' => $dept->id, 'name' => '弁当', 'unit_price' => 333, 'tax_rate' => '0.08', 'sort_order' => 2]);
        $this->makeEntry($product, '2026-03-02', 1);
        $this->makeEntry($product, '2026-03-03', 2);          // 155*3 = 465 → 税 46.5 → 46
        $this->makeEntry($reduced, '2026-03-03', 1);          // 333 → 税 26.64 → 26
        $this->makeEntry($product, '2026-04-01', 99);         // 期間外

        $this->actingAs($store)->getJson("/api/delivery/preview?customer_id={$customer->id}&period_start=2026-03-01&period_end=2026-03-31")
            ->assertOk()
            ->assertJsonPath('entries.0.name', '総務部')
            ->assertJsonPath('entries.0.subtotal', 798)
            ->assertJsonPath('entries.0.products.0.quantity', 3)
            ->assertJsonPath('entries.0.products.0.amount', 465)
            ->assertJsonPath('tax_groups.0.tax', 46)
            ->assertJsonPath('tax_groups.0.subtotal_incl', 511)
            ->assertJsonPath('tax_groups.1.tax', 26)
            ->assertJsonPath('has_finalized', false)
            ->assertJsonPath('note_id', null)
            ->assertJsonPath('default_email_body', fn ($v) => str_contains($v, 'テスト商事 御中') && str_contains($v, '3月1日〜3月31日分'));
    }

    public function test_preview_flags_finalized_entries_and_existing_note(): void
    {
        $store = $this->makeStore();
        [$customer, , $product] = $this->makeDeliveryCustomer($store, email: null);
        $entry = $this->makeEntry($product, '2026-03-03', 2);
        $note = DeliveryNote::createWithNumber($customer->id, '2026-03-01', '2026-03-31');
        $entry->update(['delivery_note_id' => $note->id]);

        $this->actingAs($store)->getJson("/api/delivery/preview?customer_id={$customer->id}&period_start=2026-03-01&period_end=2026-03-31")
            ->assertOk()
            ->assertJsonPath('has_finalized', true)
            ->assertJsonPath('note_id', $note->id)
            ->assertJsonPath('default_email_body', '');
    }
}
