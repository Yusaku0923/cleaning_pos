<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ClientError;
use App\Models\Clothes;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** 衣類マスタ・プリンタ設定・キャッシュクリア・クライアントエラー収集 (Task 10)。 */
class MasterSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $email = 's@example.com'): Store
    {
        return Store::create([
            'name' => '店', 'email' => $email, 'password' => 'x', 'address' => '住所', 'phone_number' => '000',
        ]);
    }

    public function test_printer_ip_must_be_ipv4(): void
    {
        $store = $this->makeStore();

        foreach (['', 'abc', '999.1.1.1', '192.168.0', '::1', '192.168.0.1; ls', '192.168.0.1.5'] as $bad) {
            $this->actingAs($store)->post('/settings/printer', ['printer_ip' => $bad])->assertSessionHasErrors('printer_ip');
        }
        $this->assertNull(Setting::get('printer_ip'));

        $this->actingAs($store)->post('/settings/printer', ['printer_ip' => '192.168.0.50'])->assertRedirect();
        $this->assertSame('192.168.0.50', Setting::get('printer_ip'));
    }

    public function test_printer_ip_accepts_legacy_four_octet_form(): void
    {
        $store = $this->makeStore();

        $this->actingAs($store)->post('/settings/printer', ['ip' => ['10', '0', '1', '7']])->assertRedirect();
        $this->assertSame('10.0.1.7', Setting::get('printer_ip'));
    }

    public function test_receipt_edit_shows_current_ip_in_four_boxes(): void
    {
        $store = $this->makeStore();
        Setting::set('printer_ip', '192.168.5.9');

        $this->actingAs($store)->get('/receipt/edit')
            ->assertOk()
            ->assertSee('プリンターのローカルIP')
            ->assertSee('name="ip[0]" value="192"', false)
            ->assertSee('name="ip[3]" value="9"', false);
    }

    public function test_cache_clear_does_not_shell_out(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/CacheController.php'));
        $this->assertNotEmpty($source);
        $this->assertDoesNotMatchRegularExpression('/\b(exec|shell_exec|system|passthru|proc_open|popen)\s*\(|Process::|Component\\\\Process/', $source);

        $store = $this->makeStore();
        $this->actingAs($store)->post('/cache/clear')->assertStatus(302);
    }

    public function test_cache_clear_requires_login(): void
    {
        $this->post('/cache/clear')->assertRedirect('/login');
    }

    public function test_client_error_is_stored_without_login(): void
    {
        $this->postJson('/api/client-error', [
            'error_type' => 'error', 'message' => 'boom', 'stack' => 's', 'url' => 'http://x/y', 'extra_data' => ['a' => 1],
        ])->assertOk()->assertJson(['success' => true]);

        $row = ClientError::first();
        $this->assertSame('boom', $row->message);
        $this->assertSame(['a' => 1], $row->extra_data);

        $this->postJson('/api/client-error', ['error_type' => str_repeat('x', 21), 'message' => 'm'])->assertStatus(422);
        $this->postJson('/api/client-error', ['error_type' => 'error'])->assertStatus(422);
    }

    public function test_client_error_is_rate_limited(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->postJson('/api/client-error', ['error_type' => 'warn', 'message' => "m$i"])->assertOk();
        }
        $this->postJson('/api/client-error', ['error_type' => 'warn', 'message' => 'over'])->assertStatus(429);
        $this->assertSame(60, ClientError::count());
    }

    public function test_clothes_master_page_lists_categories_except_id_1(): void
    {
        $store = $this->makeStore();
        $this->actingAs($store);
        Category::create(['id' => 1, 'name' => '除外']);
        $c = Category::create(['id' => 2, 'name' => 'シャツ類']);
        Clothes::create(['category_id' => $c->id, 'sort_key' => 1, 'name' => 'ワイシャツ', 'name_kana' => 'ワイシャツ', 'price' => 200]);

        $this->get('/clothes/create')->assertOk()->assertSee('clothes-mst-component', false)
            ->assertSee(trim(json_encode('ワイシャツ'), '"'), false)->assertDontSee(trim(json_encode('除外'), '"'), false);
    }

    public function test_clothes_crud(): void
    {
        $store = $this->makeStore();
        $this->actingAs($store);
        $cat = Category::create(['name' => 'シャツ類']);

        $res = $this->postJson('/api/clothes/store', [
            'category_id' => $cat->id, 'name' => 'ワイシャツ', 'name_kana' => 'ワイシャツ', 'price' => 200, 'tag_count' => 1,
        ])->assertOk()->assertJson(['ok' => true]);
        $first = Clothes::first();
        $this->assertSame(1, (int) $first->sort_key);
        $this->assertSame('ﾜｲｼｬﾂ', $first->name_kana); // 旧と同じ mb_convert_kana('rnk'): カナは半角に
        $this->assertSame($store->id, (int) $first->store_id);
        $this->assertCount(1, $res->json('category.0.clothes'));

        $this->postJson('/api/clothes/update', [
            'id' => $first->id, 'category_id' => $cat->id, 'name' => 'Yシャツ', 'name_kana' => 'ワイ', 'price' => 250, 'tag_count' => 2,
        ])->assertOk();
        $this->assertSame(1, Clothes::count());
        $this->assertSame('Yシャツ', Clothes::first()->name);
        $this->assertSame(1, (int) Clothes::first()->sort_key); // 並び順は引き継ぐ

        $this->postJson('/api/clothes/delete', ['id' => Clothes::first()->id, 'category_id' => $cat->id])->assertOk();
        $this->assertSame(0, Clothes::count());
    }

    public function test_clothes_cannot_be_changed_across_stores(): void
    {
        $a = $this->makeStore('a@example.com');
        $b = $this->makeStore('b@example.com');
        $this->actingAs($a);
        $cat = Category::create(['name' => 'A']);
        $item = Clothes::create(['category_id' => $cat->id, 'sort_key' => 1, 'name' => 'x', 'name_kana' => 'x', 'price' => 1]);

        $this->actingAs($b);
        $this->postJson('/api/clothes/delete', ['id' => $item->id, 'category_id' => $cat->id])->assertNotFound();
        $this->postJson('/api/clothes/update', [
            'id' => $item->id, 'category_id' => $cat->id, 'name' => 'z', 'name_kana' => 'z', 'price' => 1, 'tag_count' => 1,
        ])->assertNotFound();
        $this->assertSame(1, Clothes::withoutGlobalScopes()->count());
    }

    public function test_clothes_store_validates_input(): void
    {
        $this->actingAs($this->makeStore());
        $this->postJson('/api/clothes/store', ['category_id' => 999999, 'name' => '', 'price' => -1])->assertStatus(422);
    }

    public function test_category_store(): void
    {
        $this->actingAs($this->makeStore());
        $this->postJson('/api/category/store', ['name' => 'ニット'])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('ニット', Category::first()->name);
        $this->postJson('/api/category/store', ['name' => ''])->assertStatus(422);
    }
}
