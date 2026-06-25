<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Store;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDepartment;
use App\Models\DeliveryProduct;

class DeliveryProductControllerTest extends TestCase
{
    private function actingAsStore()
    {
        $store = Store::first();
        if (!$store) $this->markTestSkipped('No Store record in DB');
        return $this->actingAs($store);
    }

    /** テスト用の顧客＋部署を作成。後始末は顧客削除で cascade。 */
    private function makeCustomerWithDepartment(): array
    {
        $customer = DeliveryCustomer::create(['name' => 'テスト顧客_'.uniqid()]);
        $department = DeliveryDepartment::create([
            'delivery_customer_id' => $customer->id,
            'name' => 'テスト部署',
        ]);
        return [$customer, $department];
    }

    public function test_product_index_page_loads()
    {
        [$customer, $department] = $this->makeCustomerWithDepartment();

        $this->actingAsStore()
            ->get("/delivery/{$customer->id}/products")
            ->assertOk();

        $customer->delete();
    }

    public function test_create_page_has_no_unprocessed_blade_directive()
    {
        [$customer, $department] = $this->makeCustomerWithDepartment();

        // @selected はLaravel9+のディレクティブ。L8では未処理のまま出力され、
        // Vueが @ を v-on として解釈しテンプレートコンパイルが壊れ白画面になる。
        $this->actingAsStore()
            ->get("/delivery/{$customer->id}/products/create?department_id={$department->id}")
            ->assertOk()
            ->assertDontSee('@selected', false);

        $customer->delete();
    }

    public function test_can_store_product()
    {
        [$customer, $department] = $this->makeCustomerWithDepartment();

        $this->actingAsStore()
            ->post("/delivery/{$customer->id}/products", [
                'delivery_department_id' => $department->id,
                'name' => 'ワイシャツ',
                'unit_price' => 250,
                'tax_rate' => '0.10',
            ])
            ->assertRedirect("/delivery/{$customer->id}/products");

        $this->assertDatabaseHas('delivery_products', [
            'delivery_department_id' => $department->id,
            'name' => 'ワイシャツ',
            'unit_price' => 250,
        ]);

        $customer->delete();
    }

    public function test_store_auto_assigns_sort_order()
    {
        [$customer, $department] = $this->makeCustomerWithDepartment();
        DeliveryProduct::create([
            'delivery_department_id' => $department->id,
            'name' => '既存商品',
            'unit_price' => 100,
            'tax_rate' => 0.10,
            'sort_order' => 5,
        ]);

        $this->actingAsStore()
            ->post("/delivery/{$customer->id}/products", [
                'delivery_department_id' => $department->id,
                'name' => '新商品',
                'unit_price' => 300,
                'tax_rate' => '0.10',
            ]);

        $this->assertDatabaseHas('delivery_products', [
            'delivery_department_id' => $department->id,
            'name' => '新商品',
            'sort_order' => 6,
        ]);

        $customer->delete();
    }

    public function test_can_update_product()
    {
        [$customer, $department] = $this->makeCustomerWithDepartment();
        $product = DeliveryProduct::create([
            'delivery_department_id' => $department->id,
            'name' => '旧名',
            'unit_price' => 100,
            'tax_rate' => 0.10,
        ]);

        $this->actingAsStore()
            ->put("/delivery/{$customer->id}/products/{$product->id}", [
                'delivery_department_id' => $department->id,
                'name' => '新名',
                'unit_price' => 500,
                'tax_rate' => '0.08',
            ])
            ->assertRedirect("/delivery/{$customer->id}/products");

        $this->assertDatabaseHas('delivery_products', [
            'id' => $product->id,
            'name' => '新名',
            'unit_price' => 500,
            'tax_rate' => 0.08,
        ]);

        $customer->delete();
    }

    public function test_rejects_department_not_belonging_to_customer()
    {
        [$customer, $department] = $this->makeCustomerWithDepartment();
        [$otherCustomer, $otherDepartment] = $this->makeCustomerWithDepartment();

        $this->actingAsStore()
            ->post("/delivery/{$customer->id}/products", [
                'delivery_department_id' => $otherDepartment->id,
                'name' => '不正商品',
                'unit_price' => 100,
                'tax_rate' => '0.10',
            ])
            ->assertSessionHasErrors('delivery_department_id');

        $this->assertDatabaseMissing('delivery_products', ['name' => '不正商品']);

        $customer->delete();
        $otherCustomer->delete();
    }

    public function test_validation_errors_on_invalid_input()
    {
        [$customer, $department] = $this->makeCustomerWithDepartment();

        $this->actingAsStore()
            ->post("/delivery/{$customer->id}/products", [
                'delivery_department_id' => $department->id,
                'name' => '',
                'unit_price' => 'abc',
                'tax_rate' => '0.05',
            ])
            ->assertSessionHasErrors(['name', 'unit_price', 'tax_rate']);

        $customer->delete();
    }
}
