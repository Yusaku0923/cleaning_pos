<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;


class CustomerController extends Controller
{
    public function search(Request $request) {
        $customers = [];
        if (!empty($request['keyword'])) {
            $sub_query = Order::select(DB::raw('max(created_at) as latest_visit'), 'customer_id');
            $sub_query->groupBy('customer_id');

            $customers = Customer::query();
            $customers->select('customers.*', 'visits.latest_visit');
            $customers->leftJoinSub($sub_query, 'visits', 'customers.id', 'visits.customer_id');
            $customers->where(function ($query) use ($request) {
                $query->where('customers.name', 'like', '%'.$request['keyword'].'%')
                ->orWhere('customers.name_kana', 'like', '%'.$request['keyword'].'%')
                ->orWhere('customers.phone_number', 'like', '%'.$request['keyword'].'%');
            })->where('customers.manager_id', $request['manager_id']);
            $customers->orderBy('visits.latest_visit', 'desc');
            $customers->orderBy('customers.number_of_visits', 'desc');

            $customers = $customers->get()->toArray();
        }

        return response()->json([
            'customers' => $customers
        ]);
    }
}
