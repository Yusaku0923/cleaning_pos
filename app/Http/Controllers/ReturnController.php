<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Order;

class ReturnController extends Controller
{
    public function index() {
        if (session()->exists('customer_id')) {
            $customer = Customer::find(session()->get('customer_id'));
            $model = new Order();
            $unhanded_orders = $model->fetchOrders($customer['id'], 'unhanded');

            return view('return.index')->with([
                'customer' => $customer,
                'orders' => $unhanded_orders,
            ]);
        } else {
            return redirect()->route('home');
        }
    }
}
