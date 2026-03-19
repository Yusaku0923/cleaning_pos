<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDepartment;
use App\Models\DeliveryProduct;
use App\Models\DeliveryDailyEntry;

class DeliveryEntryApiTest extends TestCase
{
    private function actingAsStore()
    {
        // Store is the Authenticatable model (implements Illuminate\Foundation\Auth\User)
        $store = \App\Models\Store::first();
        if (!$store) $this->markTestSkipped('No store in DB - run seeder first');
        return $this->actingAs($store);
    }

    public function test_can_fetch_customers_with_departments()
    {
        $this->actingAsStore()
            ->getJson('/api/delivery/customers')
            ->assertOk()
            ->assertJsonStructure([
                'customers' => [['id', 'name']]
            ]);
    }

    public function test_can_upsert_entry()
    {
        $product = DeliveryProduct::first();
        if (!$product) $this->markTestSkipped('No products in DB - run seeder first');

        $this->actingAsStore()
            ->postJson('/api/delivery/entries', [
                'entries' => [
                    ['delivery_product_id' => $product->id, 'date' => '2026-03-19', 'quantity' => 10]
                ]
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('delivery_daily_entries', [
            'delivery_product_id' => $product->id,
            'date' => '2026-03-19',
            'quantity' => 10,
        ]);

        // Cleanup
        DeliveryDailyEntry::where('date', '2026-03-19')->delete();
    }

    public function test_upsert_updates_existing_entry()
    {
        $product = DeliveryProduct::first();
        if (!$product) $this->markTestSkipped('No products in DB - run seeder first');

        DeliveryDailyEntry::create([
            'delivery_product_id' => $product->id,
            'date' => '2026-03-20',
            'quantity' => 5,
        ]);

        $this->actingAsStore()
            ->postJson('/api/delivery/entries', [
                'entries' => [
                    ['delivery_product_id' => $product->id, 'date' => '2026-03-20', 'quantity' => 15]
                ]
            ])
            ->assertOk();

        $this->assertDatabaseHas('delivery_daily_entries', [
            'delivery_product_id' => $product->id,
            'date' => '2026-03-20',
            'quantity' => 15,
        ]);
        $this->assertEquals(
            1,
            DeliveryDailyEntry::where('delivery_product_id', $product->id)->where('date', '2026-03-20')->count()
        );

        // Cleanup
        DeliveryDailyEntry::where('date', '2026-03-20')->delete();
    }

    public function test_can_fetch_entries_by_customer_and_date()
    {
        $customer = DeliveryCustomer::first();
        if (!$customer) $this->markTestSkipped('No customers in DB - run seeder first');

        $this->actingAsStore()
            ->getJson("/api/delivery/entries?customer_id={$customer->id}&date=2026-03-19")
            ->assertOk()
            ->assertJsonStructure(['entries']);
    }

    public function test_can_fetch_entry_status()
    {
        $customer = DeliveryCustomer::first();
        if (!$customer) $this->markTestSkipped('No customers in DB - run seeder first');

        $this->actingAsStore()
            ->getJson("/api/delivery/entry-status?customer_id={$customer->id}")
            ->assertOk()
            ->assertJsonStructure(['dates']);
    }
}
