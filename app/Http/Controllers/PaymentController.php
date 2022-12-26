<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Store;
use App\Models\Customer;
use App\Models\Order;

class PaymentController extends Controller
{
    public function index() {
        if (!session()->exists('customer_id')) {
            return redirect()->route('home');
        }
        
        $customer = Customer::find(session()->get('customer_id'));
        $model = new Order();
        $unpaid_orders = $model->fetchUnpaidOrders($customer['id']);

        return view('payment.index')->with([
            'title' => '入　金',
            'customer' => $customer,
            'orders' => $unpaid_orders,
        ]);
    }
}
