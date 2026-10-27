<?php

namespace App\Http\Controllers;

use App\Domain\Order\OrderQueries;
use App\Models\Customer;
use App\Models\CustomerInformation;
use Illuminate\Http\Request;

/** 旧 ReturnController::index (お渡し)。顧客は URL (?c=) から。 */
class ReturnController extends Controller
{
    public function index(Request $request, OrderQueries $orders)
    {
        /** @var Customer|null $customer */
        $customer = $request->attributes->get('customer');
        if ($customer === null) {
            return redirect(pos_url('home'));
        }

        return view('return.index')->with([
            'title' => 'お　渡　し',
            'customer' => $customer,
            'orders' => $orders->fetchUnhandedOrders($customer->id),
            'customer_info' => CustomerInformation::forScreen($customer),
        ]);
    }
}
