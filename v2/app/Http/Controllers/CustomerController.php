<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customers\StoreRequest;
use App\Http\Requests\Customers\UpdateRequest;
use App\Models\Customer;
use App\Models\Manager;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 旧 CustomerController。選択中の担当者・顧客は URL (?m=&c=) で持ち、セッションには書かない。
 */
class CustomerController extends Controller
{
    public function list(Request $request)
    {
        $manager = $request->attributes->get('manager');
        if ($manager === null) {
            return redirect()->route('home');
        }

        $customers = Customer::where('manager_id', $manager->id)
            ->orderBy('name_kana', 'asc')
            ->get();

        return view('customers.list')->with([
            'title' => '顧　客　一　覧',
            'customers' => $customers,
        ]);
    }

    public function search(Request $request)
    {
        $manager = $request->attributes->get('manager');

        $sub_query = Order::select(DB::raw('max(created_at) as latest_visit'), 'customer_id');
        $sub_query->groupBy('customer_id');

        $customers = Customer::query();
        $customers->select('customers.*', 'visits.latest_visit');
        $customers->leftJoinSub($sub_query, 'visits', 'customers.id', 'visits.customer_id');
        $customers->where('customers.manager_id', $manager?->id);
        $customers->orderBy('visits.latest_visit', 'desc');
        $customers->orderBy('customers.number_of_visits', 'desc');
        $customers->limit(50);
        $customers = $customers->get()->toArray();

        return view('customers.search')->with([
            'title' => '顧　客　検　索',
            'manager_id' => $manager?->id,
            'customers' => $customers,
        ]);
    }

    /** 旧 customer/select/{id}。顧客を URL に付けたホームへリダイレクトするだけ (セッションには書かない)。 */
    public function select(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $manager = $request->attributes->get('manager');

        // TODO(Task 7): 旧はここ (GET) で顧客表示に push していた (請求書払いでない顧客のみ、
        //   ['event' => 'customer', 'name' => $customer->name])。v2 は GET では送らず、
        //   ホーム画面の Vue から POST /api/customer-display を呼ぶ。

        return redirect()->route('home', ['m' => $manager?->id ?? $customer->manager_id, 'c' => $customer->id]);
    }

    public function clear(Request $request)
    {
        return redirect()->route('home', array_filter(['m' => $request->attributes->get('manager')?->id]));
    }

    public function create()
    {
        return view('customers.create')->with([
            'title' => '新　規　登　録',
        ]);
    }

    public function store(StoreRequest $request)
    {
        /** @var Manager|null $manager */
        $manager = $request->attributes->get('manager');
        // 担当者は URL の m (自店舗のスコープで解決済み) だけを使い、入力の manager_id は見ない
        abort_if($manager === null, 404);

        $customer = Customer::create([
            'manager_id' => $manager->id,
            'name' => $request->name,
            'name_kana' => mb_convert_kana($request->name_kana, 'rnk'),
            'phone_number' => $request->phone_number,
            'is_invoice' => (bool) $request->is_invoice,
            'needs_payment_confirmation' => (bool) $request->check_payment,
            'needs_return_confirmation' => (bool) $request->check_return,
            'cutoff_date' => $request->cutoff_date ?? 99,
        ]);

        // TODO(Task 7): 旧はここで顧客表示に push していた (請求書払いでない顧客のみ)。
        //   v2 はホーム画面の Vue から POST /api/customer-display を呼ぶ。

        return redirect()->route('home', ['m' => $manager->id, 'c' => $customer->id]);
    }

    public function update(UpdateRequest $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $customer->name = $request->name;
        $customer->name_kana = mb_convert_kana($request->name_kana, 'rnk');
        $customer->phone_number = $request->phone_number;
        $customer->is_invoice = (bool) $request->is_invoice;
        $customer->needs_payment_confirmation = (bool) $request->check_payment;
        $customer->needs_return_confirmation = (bool) $request->check_return;
        $customer->cutoff_date = $request->cutoff_date ?? $customer->cutoff_date;
        $customer->save();

        return redirect()->to(pos_url('home'));
    }

    public function destroy(Request $request, $id)
    {
        $customer = Customer::find($id);
        // 選択中の顧客を消したら、戻り先の URL から外す (旧: セッションの顧客を消す)
        $selected = $request->attributes->get('customer');
        $back = fn () => redirect()->to(pos_url('customer.list', $selected && (int) $selected->id === (int) $id ? ['c' => null] : []));

        if (! $customer) {
            return $back()->with('error', '顧客が見つかりません。');
        }

        $orderCount = Order::where('customer_id', $id)->count();

        if ($orderCount > 0) {
            // 注文がある場合はソフトデリート
            $customer->delete();

            return $back()->with('success', '顧客を削除しました。（注文履歴は保持されます）');
        }

        // 注文がない場合は完全削除 (CS 情報は外部キーがあるので先に消す)
        DB::transaction(function () use ($customer) {
            $customer->information()->withTrashed()->forceDelete();
            $customer->forceDelete();
        });

        return $back()->with('success', '顧客を削除しました。');
    }
}
