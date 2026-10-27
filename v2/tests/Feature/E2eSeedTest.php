<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Manager;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class E2eSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_is_idempotent_and_resets_e2e_orders(): void
    {
        $this->artisan('e2e:seed')->assertSuccessful();
        $this->artisan('e2e:seed')->assertSuccessful();

        $this->assertSame(1, Store::where('email', 'test@gmail.com')->count());
        $manager = Manager::where('name', 'E2E担当')->sole();
        $this->assertSame(['E2E請求顧客', 'E2E顧客'], Customer::where('manager_id', $manager->id)->orderBy('name')->pluck('name')->all());

        // 注文とタグ採番を進めても、もう一度流せば初期状態に戻る
        $customer = Customer::where('name', 'E2E顧客')->sole();
        $t = ['created_at' => now(), 'updated_at' => now()];
        $order = DB::table('orders')->insertGetId(['store_id' => $manager->store_id, 'manager_id' => $manager->id, 'customer_id' => $customer->id, 'amount' => 500, 'reduction' => 0, 'payment' => 500] + $t);
        DB::table('order_items')->insert(['order_id' => $order, 'clothes_id' => DB::table('clothes')->where('name', 'E2E衣類')->value('id'), 'tag' => '1-000'] + $t);
        DB::table('tag_counters')->where('manager_id', $manager->id)->update(['value' => 1005]);
        $customer->update(['total_sales' => 500, 'number_of_visits' => 1]);

        $this->artisan('e2e:seed')->assertSuccessful();
        $this->assertSame(0, DB::table('orders')->count());
        $this->assertSame(1000, (int) DB::table('tag_counters')->where('manager_id', $manager->id)->value('value'));
        $this->assertSame(0, (int) $customer->fresh()->total_sales);
    }

    public function test_seed_creates_delivery_customer_and_clears_its_entries(): void
    {
        $this->artisan('e2e:seed')->assertSuccessful();
        $this->artisan('e2e:seed')->assertSuccessful();

        $this->assertSame(1, DB::table('delivery_customers')->where('name', 'E2E納品先')->count());
        $productId = DB::table('delivery_products')->where('name', 'E2E商品')->value('id');
        $this->assertNotNull($productId);

        DB::table('delivery_daily_entries')->insert(['delivery_product_id' => $productId, 'date' => '2026-03-01', 'quantity' => 2, 'created_at' => now(), 'updated_at' => now()]);
        $this->artisan('e2e:seed')->assertSuccessful();
        $this->assertSame(0, DB::table('delivery_daily_entries')->count());
    }

    public function test_seed_refuses_production(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('e2e:seed')->assertFailed();
        $this->assertSame(0, Store::count());
    }

    // XREA の v2 (APP_ENV=production、本番のコピーの DB) では明示したときだけ流せる
    public function test_seed_runs_in_production_env_only_with_explicit_option(): void
    {
        $this->app['env'] = 'production';
        $this->artisan('e2e:seed', ['--allow-production-env' => true])->assertSuccessful();
        $this->assertSame(1, Store::where('email', 'test@gmail.com')->count());
    }

    // 本番 DB (cleaningpossystem) には、オプションを付けても・どの環境でも流さない
    public function test_seed_never_runs_against_production_database_name(): void
    {
        $default = config('database.default');
        config(["database.connections.$default.database" => 'cleaningpossystem']);

        $this->artisan('e2e:seed')->assertFailed();
        $this->app['env'] = 'production';
        $this->artisan('e2e:seed', ['--allow-production-env' => true])->assertFailed();
        $this->assertSame(0, Store::count());
    }

    // XREA の v2 DB (cleaningpossystem_dev) は切替後の本番。こちらにも流さない
    public function test_seed_never_runs_against_v2_production_database_name(): void
    {
        $default = config('database.default');
        config(["database.connections.$default.database" => 'cleaningpossystem_dev']);

        $this->artisan('e2e:seed')->assertFailed();
        $this->app['env'] = 'production';
        $this->artisan('e2e:seed', ['--allow-production-env' => true])->assertFailed();
        $this->assertSame(0, Store::count());
    }
}
