<?php

namespace App\Http\Controllers;

use App\Domain\Order\OrderQueries;
use App\Domain\Order\OrderService;
use App\Models\Customer;
use App\Models\CustomerInformation;
use App\Models\Order;
use Illuminate\Http\Request;

/** 旧 OrdersController の all_show / show / destroy (預り一覧・全体検索・直前預り取り消し)。 */
class OrderHistoryController extends Controller
{
    /** 全体検索 (一覧は画面の検索で読む。旧と同じく最初は空) */
    public function allShow()
    {
        return view('orders.all_show')->with([
            'title' => '預　り　一　覧',
        ]);
    }

    /** 選んだ顧客の預り一覧 (全件) */
    public function show(Request $request, OrderQueries $orders)
    {
        /** @var Customer|null $customer */
        $customer = $request->attributes->get('customer');
        if ($request->attributes->get('manager') === null || $customer === null) {
            return redirect(pos_url('home'));
        }

        return view('orders.show')->with([
            'title' => '預　り　一　覧',
            'customer' => $customer,
            'orders' => $orders->fetchOrders($customer->id, -1),
            'customer_info' => CustomerInformation::forScreen($customer),
        ]);
    }

    /** 直前預り取り消し (ホームのモーダル。旧と同じ DELETE /order/delete/{order_id})。他店舗の注文は 404。 */
    public function destroy(string $order_id, OrderService $service)
    {
        abort_unless(ctype_digit($order_id), 404);
        $service->delete(Order::findOrFail((int) $order_id));

        return redirect(pos_url('home'));
    }
}
