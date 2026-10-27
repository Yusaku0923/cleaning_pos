<?php

namespace Tests\Support;

use App\Models\DeliveryCustomer;
use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryDepartment;
use App\Models\DeliveryProduct;
use App\Models\Store;

/** 納品書のテスト用データ (旧テストは DB に既にある行に頼っていたので、v2 は毎回作る)。 */
trait DeliveryFixtures
{
    protected function makeStore(string $email = 'd@example.com'): Store
    {
        return Store::create([
            'name' => 'あさひ屋', 'email' => $email, 'password' => 'x',
            'postal_code' => '877-0000', 'address' => '日田市', 'phone_number' => '22-0000',
        ]);
    }

    /** @return array{0: DeliveryCustomer, 1: DeliveryDepartment, 2: DeliveryProduct} */
    protected function makeDeliveryCustomer(Store $store, string $name = 'テスト商事', ?string $email = 'client@example.com', string $taxRate = '0.10', int $price = 250): array
    {
        $customer = DeliveryCustomer::create(['store_id' => $store->id, 'name' => $name, 'email' => $email]);
        $department = DeliveryDepartment::create(['delivery_customer_id' => $customer->id, 'name' => '総務部', 'sort_order' => 1]);
        $product = DeliveryProduct::create([
            'delivery_department_id' => $department->id, 'name' => 'ワイシャツ',
            'unit_price' => $price, 'tax_rate' => $taxRate, 'sort_order' => 1,
        ]);

        return [$customer, $department, $product];
    }

    protected function makeEntry(DeliveryProduct $product, string $date, int $qty): DeliveryDailyEntry
    {
        return DeliveryDailyEntry::create(['delivery_product_id' => $product->id, 'date' => $date, 'quantity' => $qty]);
    }
}
