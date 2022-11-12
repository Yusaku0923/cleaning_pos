<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\Customer;
use App\Models\Order;

class ReturnController extends Controller
{
    public function index() {
        if (session()->exists('customer_id')) {
            $customer = Customer::find(session()->get('customer_id'));
            $model = new Order();
            $unhanded_orders = $model->fetchUnhandedOrders($customer['id']);
            $store = Store::find(Auth::id());
            $token = $store->createToken(Str::random(10));
            // dd($unhanded_orders);
            return view('return.index')->with([
                'customer' => $customer,
                'orders' => $unhanded_orders,
                'token' => $token->plainTextToken,
            ]);
        } else {
            return redirect()->route('home');
        }
    }

    public function update(Request $request) {
        dd($request);
    }
}
