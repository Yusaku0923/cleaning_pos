<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Manager;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** 旧 Api\CustomerController (顧客検索)。 */
class CustomerController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            // 自店舗の担当者だけ (Manager のスコープで解決できること)
            'manager_id' => ['required', 'integer', function ($attr, $value, $fail) {
                if (Manager::find($value) === null) {
                    $fail('担当者が見つかりません。');
                }
            }],
            'keyword' => ['nullable', 'string'],
        ]);

        $customers = [];
        $keyword = (string) $request->input('keyword', '');
        if ($keyword !== '') {
            $like = addcslashes($keyword, '\%_');
            $sub_query = Order::select(DB::raw('max(created_at) as latest_visit'), 'customer_id');
            $sub_query->groupBy('customer_id');

            $customers = Customer::query();
            $customers->select('customers.*', 'visits.latest_visit');
            $customers->leftJoinSub($sub_query, 'visits', 'customers.id', 'visits.customer_id');
            $customers->where(function ($query) use ($like) {
                $query->where('customers.name', 'like', '%'.$like.'%')
                    ->orWhere('customers.name_kana', 'like', $like.'%')
                    ->orWhere('customers.phone_number', 'like', $like.'%');
            })->where('customers.manager_id', (int) $request->input('manager_id'));
            $customers->orderBy('visits.latest_visit', 'desc');
            $customers->orderBy('customers.number_of_visits', 'desc');

            $customers = $customers->get()->toArray();
        }

        return response()->json([
            'customers' => $customers,
        ]);
    }
}
