<?php

namespace Tests\Feature;

use App\Models\DeliveryProduct;
use App\Models\Manager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\DeliveryFixtures;
use Tests\TestCase;

/** 旧 tests/Feature/DeliveryProductControllerTest の移植 (商品管理)。 */
class DeliveryProductControllerTest extends TestCase
{
    use DeliveryFixtures;
    use RefreshDatabase;

    public function test_requires_login(): void
    {
        $this->get('/delivery/1/products')->assertRedirect('/login');
    }

    public function test_product_index_page_loads(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->get("/delivery/{$customer->id}/products")
            ->assertOk()
            ->assertSee('テスト商事 ー 商品管理')
            ->assertSee('ワイシャツ')
            ->assertSee('250円')
            ->assertSee('10%');
    }

    public function test_index_shows_8_percent_as_8(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store, taxRate: '0.08');

        $this->actingAs($store)->get("/delivery/{$customer->id}/products")->assertOk()->assertSee('8%', false)->assertDontSee('0.08');
    }

    public function test_create_page_renders_form_with_selected_department(): void
    {
        $store = $this->makeStore();
        [$customer, $department] = $this->makeDeliveryCustomer($store);

        // Blade のディレクティブが未処理のまま出ると、Vue が @ を v-on と解釈して白画面になる (旧の不具合)
        $this->actingAs($store)->get("/delivery/{$customer->id}/products/create?department_id={$department->id}")
            ->assertOk()
            ->assertDontSee('@selected', false)
            ->assertSee('商品追加')
            ->assertSee('value="'.$department->id.'" selected', false);
    }

    public function test_can_store_product(): void
    {
        $store = $this->makeStore();
        [$customer, $department] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->post("/delivery/{$customer->id}/products", [
            'delivery_department_id' => $department->id,
            'name' => 'スーツ',
            'unit_price' => 250,
            'tax_rate' => '0.10',
        ])->assertRedirect("/delivery/{$customer->id}/products")->assertSessionHas('message', '商品を追加しました');

        $this->assertDatabaseHas('delivery_products', ['delivery_department_id' => $department->id, 'name' => 'スーツ', 'unit_price' => 250]);
    }

    public function test_store_auto_assigns_sort_order(): void
    {
        $store = $this->makeStore();
        [$customer, $department] = $this->makeDeliveryCustomer($store);
        DeliveryProduct::where('delivery_department_id', $department->id)->update(['sort_order' => 5]);

        $this->actingAs($store)->post("/delivery/{$customer->id}/products", [
            'delivery_department_id' => $department->id,
            'name' => '新商品',
            'unit_price' => 300,
            'tax_rate' => '0.10',
        ]);

        $this->assertDatabaseHas('delivery_products', ['delivery_department_id' => $department->id, 'name' => '新商品', 'sort_order' => 6]);
    }

    public function test_store_keeps_given_sort_order(): void
    {
        $store = $this->makeStore();
        [$customer, $department] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->post("/delivery/{$customer->id}/products", [
            'delivery_department_id' => $department->id, 'name' => '先頭', 'unit_price' => 1, 'tax_rate' => '0.08', 'sort_order' => 0,
        ]);

        $this->assertDatabaseHas('delivery_products', ['name' => '先頭', 'sort_order' => 0, 'tax_rate' => 0.08]);
    }

    public function test_can_update_product(): void
    {
        $store = $this->makeStore();
        [$customer, $department, $product] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->get("/delivery/{$customer->id}/products/{$product->id}/edit")
            ->assertOk()->assertSee('商品編集')->assertSee('更新する');

        $this->actingAs($store)->put("/delivery/{$customer->id}/products/{$product->id}", [
            'delivery_department_id' => $department->id,
            'name' => '新名',
            'unit_price' => 500,
            'tax_rate' => '0.08',
        ])->assertRedirect("/delivery/{$customer->id}/products")->assertSessionHas('message', '商品を更新しました');

        $this->assertDatabaseHas('delivery_products', ['id' => $product->id, 'name' => '新名', 'unit_price' => 500, 'tax_rate' => 0.08]);
    }

    public function test_rejects_department_not_belonging_to_customer(): void
    {
        $store = $this->makeStore();
        [$customer] = $this->makeDeliveryCustomer($store);
        [, $otherDepartment] = $this->makeDeliveryCustomer($store, '別の会社');

        $this->actingAs($store)->post("/delivery/{$customer->id}/products", [
            'delivery_department_id' => $otherDepartment->id, 'name' => '不正商品', 'unit_price' => 100, 'tax_rate' => '0.10',
        ])->assertSessionHasErrors('delivery_department_id');

        $this->assertDatabaseMissing('delivery_products', ['name' => '不正商品']);
    }

    public function test_validation_errors_on_invalid_input(): void
    {
        $store = $this->makeStore();
        [$customer, $department] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->post("/delivery/{$customer->id}/products", [
            'delivery_department_id' => $department->id, 'name' => '', 'unit_price' => 'abc', 'tax_rate' => '0.05',
        ])->assertSessionHasErrors(['name', 'unit_price', 'tax_rate']);
    }

    public function test_product_of_another_customer_is_404_on_edit_and_update(): void
    {
        $store = $this->makeStore();
        [$customer, $department] = $this->makeDeliveryCustomer($store);
        [, , $otherProduct] = $this->makeDeliveryCustomer($store, '別の会社');

        $this->actingAs($store)->get("/delivery/{$customer->id}/products/{$otherProduct->id}/edit")->assertNotFound();
        $this->actingAs($store)->put("/delivery/{$customer->id}/products/{$otherProduct->id}", [
            'delivery_department_id' => $department->id, 'name' => '乗っ取り', 'unit_price' => 1, 'tax_rate' => '0.10',
        ])->assertNotFound();
        $this->assertDatabaseMissing('delivery_products', ['name' => '乗っ取り']);
    }

    public function test_other_stores_customer_is_404(): void
    {
        $mine = $this->makeStore();
        $other = $this->makeStore('o@example.com');
        [$theirs, $theirDept, $theirProduct] = $this->makeDeliveryCustomer($other);

        $this->actingAs($mine)->get("/delivery/{$theirs->id}/products")->assertNotFound();
        $this->actingAs($mine)->get("/delivery/{$theirs->id}/products/create")->assertNotFound();
        $this->actingAs($mine)->post("/delivery/{$theirs->id}/products", [
            'delivery_department_id' => $theirDept->id, 'name' => '侵入', 'unit_price' => 1, 'tax_rate' => '0.10',
        ])->assertNotFound();
        $this->actingAs($mine)->get("/delivery/{$theirs->id}/products/{$theirProduct->id}/edit")->assertNotFound();
        $this->assertDatabaseMissing('delivery_products', ['name' => '侵入']);
    }

    public function test_links_and_redirect_keep_manager_context(): void
    {
        $store = $this->makeStore();
        $manager = Manager::create(['store_id' => $store->id, 'name' => '担当']);
        [$customer, $department] = $this->makeDeliveryCustomer($store);

        $this->actingAs($store)->get("/delivery/{$customer->id}/products?m={$manager->id}")
            ->assertOk()
            ->assertSee("/delivery/{$customer->id}/products/create?m={$manager->id}&amp;department_id={$department->id}", false);

        $this->actingAs($store)->post("/delivery/{$customer->id}/products?m={$manager->id}", [
            'delivery_department_id' => $department->id, 'name' => 'X', 'unit_price' => 1, 'tax_rate' => '0.10',
        ])->assertRedirect("/delivery/{$customer->id}/products?m={$manager->id}");
    }
}
