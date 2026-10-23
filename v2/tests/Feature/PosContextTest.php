<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolvePosContext;
use App\Models\Customer;
use App\Models\Manager;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 選択中の担当者・顧客は URL (?m=&c=) で持つ (Review Focus 4: タブ 2 つで別の顧客)。
 * セッションには書かない。
 */
class PosContextTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private function makeStore(string $email): Store
    {
        return Store::create([
            'name' => '店', 'email' => $email, 'password' => 'x', 'address' => '住所', 'phone_number' => '000',
        ]);
    }

    private function makeManager(Store $store, string $name, ?int $id = null): Manager
    {
        $t = ['created_at' => now(), 'updated_at' => now()];
        $id = DB::table('managers')->insertGetId(array_filter(['id' => $id]) + ['store_id' => $store->id, 'name' => $name] + $t);

        return Manager::withoutGlobalScopes()->findOrFail($id);
    }

    private function makeCustomer(Manager $manager, string $name, ?int $id = null, array $extra = []): Customer
    {
        $t = ['created_at' => now(), 'updated_at' => now()];
        $id = DB::table('customers')->insertGetId(array_filter(['id' => $id]) + ['manager_id' => $manager->id, 'name' => $name, 'name_kana' => 'テスト'] + $extra + $t);

        return Customer::withoutGlobalScopes()->findOrFail($id);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = $this->makeStore('a@example.com');
        $this->makeManager($this->store, '担当1', 1);
        $m2 = $this->makeManager($this->store, '担当2', 2);
        $this->makeCustomer($m2, '十番顧客', 10);
        $this->makeCustomer($m2, '十一番顧客', 11);
    }

    public function test_context_comes_from_query_not_session(): void
    {
        $seen = null;
        Route::middleware(['web', 'auth', ResolvePosContext::class])
            ->get('/_ctx', function (Request $request) use (&$seen) {
                $seen = [$request->attributes->get('manager'), $request->attributes->get('customer')];

                return 'ok';
            });

        // セッションに旧方式の値が残っていても、URL が優先 (セッションは見ない)
        $this->actingAs($this->store)
            ->withSession(['manager_id' => 1, 'customer_id' => 11])
            ->get('/_ctx?m=2&c=10')->assertOk();

        $this->assertInstanceOf(Manager::class, $seen[0]);
        $this->assertSame(2, $seen[0]->id);
        $this->assertInstanceOf(Customer::class, $seen[1]);
        $this->assertSame(10, $seen[1]->id);

        // URL に無ければ未選択 (セッションから補わない)
        $this->actingAs($this->store)
            ->withSession(['manager_id' => 2, 'customer_id' => 10])
            ->get('/_ctx')->assertOk();
        $this->assertNull($seen[0]);
        $this->assertNull($seen[1]);

        // ホーム画面でも URL の顧客が出る
        $this->actingAs($this->store)->get('/?m=2&c=10')
            ->assertOk()->assertSee('十番顧客　様')->assertDontSee('十一番顧客');
    }

    public function test_unknown_customer_id_is_404(): void
    {
        $this->actingAs($this->store);
        $this->get('/?m=2&c=999')->assertNotFound();
        $this->get('/?m=999')->assertNotFound();
        $this->get('/?m=2&c=abc')->assertNotFound();

        // 他店舗の顧客・担当者も「無い」扱い
        $other = $this->makeStore('b@example.com');
        $om = $this->makeManager($other, '他店担当');
        $oc = $this->makeCustomer($om, '他店顧客');
        $this->get("/?m=2&c={$oc->id}")->assertNotFound();
        $this->get("/?m={$om->id}")->assertNotFound();
        $this->get("/customer/select/{$oc->id}?m=2")->assertNotFound();
    }

    public function test_two_tabs_do_not_share_customer(): void
    {
        $this->actingAs($this->store);
        // 同じセッションで、タブ A (c=10) とタブ B (c=11) を交互に開く
        foreach ([10, 11, 10, 11] as $c) {
            $res = $this->get("/?m=2&c={$c}")->assertOk();
            if ($c === 10) {
                $res->assertSee('十番顧客　様')->assertDontSee('十一番顧客');
            } else {
                $res->assertSee('十一番顧客　様')->assertDontSee('十番顧客');
            }
        }
        // 画面内のリンクも自分のタブの顧客を引き継ぐ
        $this->get('/?m=2&c=11')->assertSee(e(route('customer.list', ['m' => 2, 'c' => 11])), false);
    }

    public function test_select_route_redirects_without_touching_session(): void
    {
        $this->actingAs($this->store);

        $res = $this->get('/customer/select/10?m=2');
        $res->assertRedirect(route('home', ['m' => 2, 'c' => 10]));
        $res->assertSessionMissing('customer_id');
        $res->assertSessionMissing('customer_info');

        $res = $this->get('/manager/update/2');
        $res->assertRedirect(route('home', ['m' => 2]));
        $res->assertSessionMissing('manager_id');
        $res->assertSessionMissing('manager_name');

        // 同じ担当者を選び直したときは顧客を引き継ぐ (旧: 担当者が一致すれば顧客はそのまま)
        $this->get('/manager/update/2?m=2&c=10')->assertRedirect(route('home', ['m' => 2, 'c' => 10]));
        // 別の担当者に替えたら顧客は外れる
        $this->get('/manager/update/1?m=2&c=10')->assertRedirect(route('home', ['m' => 1]));

        $this->get('/customer/clear?m=2&c=10')->assertRedirect(route('home', ['m' => 2]));
        $this->assertNull(session('customer_id'));
        $this->assertNull(session('manager_id'));
    }

    public function test_customer_of_other_manager_is_not_selected(): void
    {
        // 旧ホーム: 担当者と顧客の担当者が一致しなければ顧客は未選択
        $this->actingAs($this->store)->get('/?m=1&c=10')
            ->assertOk()->assertDontSee('十番顧客')
            ->assertSee(e(route('customer.list', ['m' => 1])), false)->assertDontSee('c=10');
    }

    public function test_pos_url_carries_manager_and_customer(): void
    {
        $this->actingAs($this->store)->get('/?m=2&c=10')->assertOk();
        $this->assertSame(route('customer.list', ['m' => 2, 'c' => 10]), pos_url('customer.list'));
        $this->assertSame(route('home', ['m' => 2]), pos_url('home', ['c' => null]));
        $this->assertSame(route('customer.select', ['id' => 11, 'm' => 2, 'c' => 10]), pos_url('customer.select', ['id' => 11]));
    }

    public function test_customer_store_forces_manager_from_url_and_store_scope(): void
    {
        $this->actingAs($this->store);
        $other = $this->makeStore('b@example.com');
        $om = $this->makeManager($other, '他店担当');

        // 他店舗の担当者を URL に付けても作れない
        $this->post("/customer/store?m={$om->id}", ['name' => '新規', 'name_kana' => 'シンキ', 'is_invoice' => '0'])
            ->assertNotFound();
        // 入力の manager_id は無視して URL の担当者で作る
        $res = $this->post('/customer/store?m=2', [
            'name' => '新規', 'name_kana' => 'シンキ', 'is_invoice' => '0', 'check_payment' => '1', 'check_return' => '1',
            'cutoff_date' => '99', 'manager_id' => $om->id,
        ]);
        $created = Customer::where('name', '新規')->firstOrFail();
        $this->assertSame(2, (int) $created->manager_id);
        $this->assertSame('ｼﾝｷ', $created->name_kana); // 旧と同じ mb_convert_kana('rnk') = 半角カナで保存
        $res->assertRedirect(route('home', ['m' => 2, 'c' => $created->id]));
        $res->assertSessionMissing('customer_id');

        // 担当者未選択では作れない
        $this->post('/customer/store', ['name' => 'x', 'name_kana' => 'x', 'is_invoice' => '0'])->assertNotFound();
    }

    public function test_customer_info_api_is_scoped_to_store(): void
    {
        $other = $this->makeStore('b@example.com');
        $oc = $this->makeCustomer($this->makeManager($other, '他店担当'), '他店顧客');

        $this->actingAs($this->store);
        $this->postJson('/api/customer_info/store', ['customer_id' => $oc->id, 'information' => 'x'])->assertStatus(422);
        $this->postJson('/api/customer_info/store', ['customer_id' => 10, 'information' => 'メモ'])
            ->assertOk()->assertJsonPath('info.0.information', 'メモ');
        $this->postJson('/api/customer/search', ['manager_id' => 2, 'keyword' => '十一'])
            ->assertOk()->assertJsonCount(1, 'customers')->assertJsonPath('customers.0.id', 11);
        $this->postJson('/api/customer/search', ['manager_id' => 999, 'keyword' => '十'])->assertStatus(422);
    }

    public function test_customer_update_and_delete_keep_url_context_and_scope(): void
    {
        $other = $this->makeStore('b@example.com');
        $oc = $this->makeCustomer($this->makeManager($other, '他店担当'), '他店顧客');
        $this->actingAs($this->store);

        $input = ['name' => '改名', 'name_kana' => 'カイメイ', 'is_invoice' => '1', 'check_payment' => '1', 'check_return' => '0', 'cutoff_date' => '20'];
        $this->post("/customer/update/{$oc->id}?m=2", $input)->assertNotFound();
        $this->post('/customer/update/10?m=2&c=10', $input)->assertRedirect(route('home', ['m' => 2, 'c' => 10]));
        $c = Customer::findOrFail(10);
        $this->assertSame(['改名', 'ｶｲﾒｲ', true, true, false, 20], [$c->name, $c->name_kana, $c->is_invoice, $c->needs_payment_confirmation, $c->needs_return_confirmation, (int) $c->cutoff_date]);

        // 他店舗の顧客は「見つかりません」で消えない
        $this->delete("/customer/delete/{$oc->id}?m=2")->assertRedirect(route('customer.list', ['m' => 2]));
        $this->assertNotNull(Customer::withoutGlobalScopes()->find($oc->id));
        // 選択中の顧客を消したら、戻り先から c を外す
        $this->delete('/customer/delete/10?m=2&c=10')->assertRedirect(route('customer.list', ['m' => 2]));
        $this->assertNull(Customer::withTrashed()->find(10));
    }
}
