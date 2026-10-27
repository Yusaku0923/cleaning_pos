<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Clothes;
use App\Models\Customer;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDepartment;
use App\Models\DeliveryProduct;
use App\Models\Manager;
use App\Models\Store;
use App\Models\TagCounter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * E2E 用の店舗・担当者・顧客を作り、毎回同じ状態に戻す (e2e/setup-test-store.sh の v2 版。同じデータ)。
 *   ログイン: test@gmail.com / Test123456
 *   納品書の取引先「E2E納品先」(部署「E2E部署」・商品「E2E商品」)
 *   担当者「E2E担当」・顧客「E2E顧客」(通常)・「E2E請求顧客」(請求)・カテゴリ「E2Eカテゴリ」・衣類「E2E衣類」
 * E2E 顧客の注文・請求を消し、集計とタグ採番を初期値に戻す (E2E 顧客以外は触らない)。
 * 本番 (APP_ENV=production) では実行しない (--allow-production-env は v2 DB の名前が FORBIDDEN_DATABASES 以外のときだけ効く)。
 * 本番 DB (旧 cleaningpossystem・XREA の v2 cleaningpossystem_dev) には、どの環境・オプションでも流さない。
 * (XREA の v2 で新旧比較の E2E を流すのは、切替前の Task 12 で終えた。v2 DB が本番になったのでもう流せない。)
 */
class E2eSeed extends Command
{
    protected $signature = 'e2e:seed
        {--allow-production-env : APP_ENV=production でも実行する (本番 DB の名前には効かない)}';

    /**
     * どんな場合も書き込まない DB 名。cleaningpossystem = 旧の本番、cleaningpossystem_dev = XREA の v2 (切替後の本番)。
     * ローカル・CI は v2 / testing なので影響しない。
     */
    public const FORBIDDEN_DATABASES = ['cleaningpossystem', 'cleaningpossystem_dev'];

    protected $description = 'E2E 用の店舗・担当者・顧客を作り、初期状態に戻す (本番では実行不可)';

    public const EMAIL = 'test@gmail.com';

    public const PASSWORD = 'Test123456';

    public function handle(): int
    {
        $default = config('database.default');
        $names = [config("database.connections.$default.database"), DB::connection()->getDatabaseName()];
        if (array_intersect($names, self::FORBIDDEN_DATABASES) !== []) {
            $this->error('本番 DB ('.implode(', ', self::FORBIDDEN_DATABASES).') には e2e:seed を実行できません。');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('allow-production-env')) {
            $this->error('本番 (APP_ENV=production) では e2e:seed を実行できません (XREA の v2 で比較するときだけ --allow-production-env)。');

            return self::FAILURE;
        }

        [$store, $manager] = DB::transaction(function () {
            $store = Store::firstOrNew(['email' => self::EMAIL]);
            $store->name = 'E2E';
            $store->password = Hash::make(self::PASSWORD);
            $store->address = $store->address ?: 'E2E';
            $store->phone_number = $store->phone_number ?: '0000000000';
            $store->save();

            // CLI では店舗スコープが外れるので store_id を明示する
            $manager = Manager::firstOrCreate(['store_id' => $store->id, 'name' => 'E2E担当']);
            TagCounter::withTrashed()->firstOrCreate(['manager_id' => $manager->id], ['value' => 1000]);

            foreach ([['E2E顧客', false], ['E2E請求顧客', true]] as [$name, $invoice]) {
                Customer::firstOrCreate(
                    ['manager_id' => $manager->id, 'name' => $name],
                    ['name_kana' => 'イーツーイー', 'is_invoice' => $invoice, 'cutoff_date' => 99],
                );
            }

            $category = Category::firstOrCreate(['store_id' => $store->id, 'name' => 'E2Eカテゴリ']);
            Clothes::updateOrCreate(
                ['store_id' => $store->id, 'name' => 'E2E衣類'],
                ['category_id' => $category->id, 'name_kana' => 'E2E', 'price' => 500, 'tag_count' => 1],
            );

            // 毎回同じ状態から始めるため、E2E 顧客の注文・請求を (論理削除分も含めて) 物理削除する
            $ids = Customer::withTrashed()->where('manager_id', $manager->id)->pluck('id');
            $orderIds = DB::table('orders')->whereIn('customer_id', $ids)->pluck('id');
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->whereIn('id', $orderIds)->delete();
            DB::table('invoices')->whereIn('customer_id', $ids)->delete();
            DB::table('customers')->whereIn('id', $ids)->update(['total_sales' => 0, 'number_of_visits' => 0]);
            DB::table('tag_counters')->where('manager_id', $manager->id)->update(['value' => 1000, 'deleted_at' => null]);

            // 納品書: E2E 取引先 (部署 1・商品 1)。記帳・納品書・送信履歴は毎回消す (E2E 取引先以外は触らない)
            $delivery = DeliveryCustomer::firstOrCreate(['store_id' => $store->id, 'name' => 'E2E納品先'], ['email' => 'e2e-delivery@example.com']);
            $department = DeliveryDepartment::firstOrCreate(['delivery_customer_id' => $delivery->id, 'name' => 'E2E部署'], ['sort_order' => 1]);
            DeliveryProduct::firstOrCreate(['delivery_department_id' => $department->id, 'name' => 'E2E商品'], ['unit_price' => 100, 'tax_rate' => '0.10', 'sort_order' => 1]);
            $productIds = DB::table('delivery_products')->where('delivery_department_id', $department->id)->pluck('id');
            DB::table('delivery_daily_entries')->whereIn('delivery_product_id', $productIds)->delete();
            DB::table('delivery_email_logs')->where('delivery_customer_id', $delivery->id)->delete();
            DB::table('delivery_notes')->where('delivery_customer_id', $delivery->id)->delete();

            return [$store, $manager];
        });

        $this->line("e2e data ok: store {$store->id} manager {$manager->id}");

        return self::SUCCESS;
    }
}
