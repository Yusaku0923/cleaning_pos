<?php

namespace App\Http\Controllers;

use App\Domain\Order\OrderQueries;
use App\Models\Customer;
use App\Models\CustomerInformation;
use Illuminate\Http\Request;

/** 旧 PaymentController::index (入金)。顧客は URL (?c=) から。 */
class PaymentController extends Controller
{
    public function index(Request $request, OrderQueries $orders)
    {
        /** @var Customer|null $customer */
        $customer = $request->attributes->get('customer');
        if ($customer === null) {
            return redirect(pos_url('home'));
        }

        return view('payment.index')->with([
            'title' => '入　金',
            'customer' => $customer,
            'orders' => $orders->fetchUnpaidOrders($customer->id),
            'customer_info' => CustomerInformation::forScreen($customer),
        ]);
    }
}
