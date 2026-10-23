<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Clothes;
use App\Models\Customer;
use App\Models\CustomerInformation;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDailyEntry;
use App\Models\DeliveryDepartment;
use App\Models\DeliveryEmailLog;
use App\Models\DeliveryNote;
use App\Models\DeliveryProduct;
use App\Models\Invoice;
use App\Models\Manager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\TagCounter;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 店舗の絞り込み (spec §6-10)。ログイン中は自店舗の行だけが見え、ID を直接指定しても他店舗の行は取れない。
 * CLI (未ログイン) では絞り込まない。
 */
class BelongsToStoreTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array<string, int>> store_id => [model => id] */
    private array $ids = [];

    private function seedStore(int $n): Store
    {
        $store = Store::create([
            'name' => "店{$n}", 'email' => "s{$n}@example.com", 'password' => 'x',
            'address' => '住所', 'phone_number' => '000',
        ]);
        $t = ['created_at' => now(), 'updated_at' => now()];
        $manager = DB::table('managers')->insertGetId(['store_id' => $store->id, 'name' => "担当{$n}"] + $t);
        $customer = DB::table('customers')->insertGetId(['manager_id' => $manager, 'name' => "客{$n}", 'name_kana' => 'キャク'] + $t);
        $info = DB::table('customer_information')->insertGetId(['customer_id' => $customer, 'information' => 'i'] + $t);
        $invoice = DB::table('invoices')->insertGetId(['manager_id' => $manager, 'customer_id' => $customer, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31'] + $t);
        $order = DB::table('orders')->insertGetId(['store_id' => $store->id, 'manager_id' => $manager, 'customer_id' => $customer, 'amount' => 100, 'reduction' => 0, 'payment' => 100] + $t);
        $category = DB::table('categories')->insertGetId(['store_id' => $store->id, 'name' => 'c'] + $t);
        $clothes = DB::table('clothes')->insertGetId(['store_id' => $store->id, 'category_id' => $category, 'name' => 'w', 'name_kana' => 'w', 'price' => 100] + $t);
        $item = DB::table('order_items')->insertGetId(['order_id' => $order, 'clothes_id' => $clothes, 'tag' => '1-001'] + $t);
        $tag = DB::table('tag_counters')->insertGetId(['manager_id' => $manager, 'value' => 1000] + $t);
        $tax = DB::table('taxes')->insertGetId(['store_id' => $store->id, 'tax' => '10'] + $t);
        $dc = DB::table('delivery_customers')->insertGetId(['store_id' => $store->id, 'name' => 'd'] + $t);
        $dd = DB::table('delivery_departments')->insertGetId(['delivery_customer_id' => $dc, 'name' => 'd'] + $t);
        $dp = DB::table('delivery_products')->insertGetId(['delivery_department_id' => $dd, 'name' => 'p', 'unit_price' => 1] + $t);
        $dn = DB::table('delivery_notes')->insertGetId(['delivery_customer_id' => $dc, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'note_number' => 1] + $t);
        $de = DB::table('delivery_daily_entries')->insertGetId(['delivery_product_id' => $dp, 'delivery_note_id' => $dn, 'date' => '2026-01-02'] + $t);
        $dl = DB::table('delivery_email_logs')->insertGetId(['delivery_note_id' => $dn, 'delivery_customer_id' => $dc, 'period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'sent_to' => 'a@example.com', 'sent_at' => now()] + $t);

        $this->ids[$store->id] = [
            Manager::class => $manager, Customer::class => $customer, CustomerInformation::class => $info,
            Invoice::class => $invoice, Order::class => $order, Category::class => $category,
            Clothes::class => $clothes, OrderItem::class => $item, TagCounter::class => $tag, Tax::class => $tax,
            DeliveryCustomer::class => $dc, DeliveryDepartment::class => $dd, DeliveryProduct::class => $dp,
            DeliveryNote::class => $dn, DeliveryDailyEntry::class => $de, DeliveryEmailLog::class => $dl,
        ];

        return $store;
    }

    public function test_logged_in_store_cannot_read_other_store_rows_by_id(): void
    {
        $mine = $this->seedStore(1);
        $other = $this->seedStore(2);

        $this->actingAs($mine);

        foreach ($this->ids[$other->id] as $class => $id) {
            $this->assertNull($class::find($id), "{$class} #{$id} of the other store must be hidden");
            $this->assertSame(1, $class::count(), "{$class} count for the logged-in store");
        }
        foreach ($this->ids[$mine->id] as $class => $id) {
            $this->assertNotNull($class::find($id), "{$class} #{$id} of my store must be visible");
        }
    }

    public function test_scope_is_off_without_login(): void
    {
        $this->seedStore(1);
        $this->seedStore(2);

        foreach (array_keys(reset($this->ids)) as $class) {
            $this->assertSame(2, $class::count(), "{$class} sees every store in CLI");
        }
    }

    public function test_soft_deleted_parent_does_not_hide_children(): void
    {
        $mine = $this->seedStore(1);
        DB::table('orders')->update(['deleted_at' => now()]);

        $this->actingAs($mine);

        $this->assertSame(1, OrderItem::count());
    }

    public function test_store_id_is_filled_on_create_when_logged_in(): void
    {
        $mine = $this->seedStore(1);
        $this->actingAs($mine);

        $category = Category::create(['name' => '新カテゴリ']);

        $this->assertSame($mine->id, (int) $category->store_id);
    }
}
