<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Manager;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderClothes;
use App\Models\TagNumber;
use App\Models\Tax;

class OrdersController extends Controller
{
    public function store(Request $request) {
        $order = Order::query()->create([
            'store_id' => Auth::id(),
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
            'reduction' => $request->reduction,
            'discount' => $request->discount,
            'payment' => $request->payment,
            'is_registered_as_invoice' => $request->invoice,
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        $tag = TagNumber::find($request->manager_id)->value('tag_number');
        $response = [];
        foreach ($request->order as $clothes_id => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $converted_tag = $this->convertTagFormat($tag);
                OrderClothes::query()->create([
                    'order_id' => $order->id,
                    'clothes_id' => $clothes_id,
                    'tag' => $converted_tag
                ]);

                    if (!isset($response[$clothes_id])) {
                        $response[$clothes_id] = $converted_tag;
                    } else if ($i >= 2 && $i === $count) {
                        $response[$clothes_id] .= '～' . $converted_tag;
                    }

                if ($tag >= 9999) {
                    $tag = 0;
                } else {
                    $tag++;
                }
            }
        }
        TagNumber::where('manager_id', $request->manager_id)
                ->update([
                    'tag_number' => $tag
                ]);

        return response()->json([
            'order_id' => $order->id
        ]);
    }

    public function fetchReceiptInfo($order_id) {

        $order = Order::find($order_id);
        $customer = Customer::find($order->customer_id);
        $store = Store::find($order->store_id);
        $manager_name = Manager::where('id', $customer->manager_id)->value('name');
        $tax = Tax::where('store_id', $order->store_id)->value('tax');

        $model = new Order;
        list($orders, $total_count) = $model->fetchReciptDetail($order_id);

        return response()->json([
            'store_name'         => $store->name,
            'store_address'      => $store->address,
            'store_tel'          => $store->phone_number,
            'customer_name_kana' => $customer->name_kana,
            'customer_name'      => $customer->name,
            'customer_tel'       => $customer->phone_number,
            'manager_name'       => $manager_name,
            'ordered_at'         => date('Y年m月d日 H時i分', strtotime($order->created_at)),
            'order_list'         => $orders,
            'total_count'        => $total_count,
            'amount'             => $order->amount,
            'discount'           => $order->discount,
            'discount_raito'     => $order->discount_raito,
            'payment'            => $order->payment,
            'tax'                => $tax,
            'paid_at'            => $order->paid_at,
        ]);
    }

    private function convertTagFormat($tag) {
        $formated_tag = '';

        if ($tag < 10) {
            $formated_tag = '0-00' . (string)$tag;
        } else if ($tag < 100) {
            $formated_tag = '0-0' . (string)$tag;
        } else if ($tag < 1000) {
            $formated_tag = '0-' . (string)$tag;
        } else {
            $formated_tag = substr((string)$tag, 0, 1) . '-' . (string)($tag % 1000);
        }

        return $formated_tag;
    }
}
