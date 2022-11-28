<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clothes;
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
use App\Models\Invoice;
use App\Services\Utility;

class OrdersController extends Controller
{
    const COLUMN_JP = [
        'neither' => '指定しない',
        'paid' => 'はい',
        'unpaid' => 'いいえ',
        'handed' => 'はい',
        'unhanded' => 'いいえ',
    ];
    public function store(Request $request) {
        $paid_at = date('Y-m-d H:i:s');
        $invoice_id = null;
        if ((boolean)$request->invoice) {
            $cutoff_date = Customer::find($request->customer_id)->value('cutoff_date');
            list($period_start, $period_end) = Utility::currentInvoicePeriod($cutoff_date);
            if ((boolean)Customer::find($request->customer_id)->value('needs_payment_confimation')) {
                $paid_at = null;
            } else {
                $paid_at = $period_end;
            }
            $model = new Invoice();
            $invoice_id = $model->existsTargetInvoice($request->customer_id);
            if (!is_null($invoice_id)) {
                $invoice = Invoice::find($invoice_id);
                $invoice->increment('amount', $request->amount);
            } else {
                $invoice = Invoice::query()->create([
                    'manager_id' => $request->manager_id,
                    'customer_id' => $request->customer_id,
                    'amount' => $request->amount,
                    'period_start' => $period_start,
                    'period_end' => $period_end,
                    'paid_at' => $paid_at,
                ]);
                $invoice_id = $invoice->id;
            }
        } else {
            if ($request->not_paid) {
                $paid_at = null;
            }
        }
        $order = Order::query()->create([
            'store_id' => Auth::id(),
            'manager_id' => $request->manager_id,
            'customer_id' => $request->customer_id,
            'invoice_id' => $invoice_id,
            'amount' => $request->amount,
            'reduction' => $request->reduction,
            'discount' => $request->discount,
            'payment' => $request->payment,
            'paid_at' => $paid_at,
        ]);

        $tag = TagNumber::find($request->manager_id)->value('tag_number');
        $response = [];
        foreach ($request->order as $clothes_id => $count) {
            $tag_count = Clothes::where('id', $clothes_id)->value('tag_count');
            for ($i = 1; $i <= $count; $i++) {
                $converted_tag = Utility::convertTagFormat($tag);
                OrderClothes::query()->create([
                    'order_id' => $order->id,
                    'clothes_id' => $clothes_id,
                    'tag' => $converted_tag
                ]);

                if ($tag_count > 1) {
                    for ($j = 1; $j < $tag_count; $j++) {
                        $tag = $this->addTag($tag);
                        $converted_tag = Utility::convertTagFormat($tag);
                        OrderClothes::query()->create([
                            'order_id' => $order->id,
                            'clothes_id' => 999,
                            'tag' => $converted_tag,
                        ]);
                    }
                } else {
                    if (!isset($response[$clothes_id])) {
                        $response[$clothes_id] = $converted_tag;
                    } else if ($i >= 2 && $i === $count) {
                        $response[$clothes_id] .= '～' . $converted_tag;
                    }
                }

                $tag = $this->addTag($tag);
            }
        }
        TagNumber::where('manager_id', $request->manager_id)
                ->update([
                    'tag_number' => $tag
                ]);
        
        $customer = Customer::find($request->customer_id);
        $customer->increment('total_sales', $request->amount);
        $customer->increment('number_of_visits');

        return response()->json([
            'order_id' => $order->id
        ]);
    }

    public function search(Request $request, Order $model) {
        $orders = $model->fetchOrders($request->customer_id, 20, $request->conditions);
        $conditions = [];
        foreach ($request->conditions as $key => $val) {
            switch ($key) {
                case 'has_paid':
                case 'has_handed':
                    if (!empty($val) && $val !== 'neither') {
                        $conditions[$key] = $this::COLUMN_JP[$val];
                    }
                    break;
                case 'after':
                    if (!empty($request->conditions['after']) || !empty($request->conditions['before'])) {
                        $conditions['term'] = empty($request->conditions['after']) ? 'なし ～ ': date('Y/m/d', strtotime($request->conditions['after'])). ' ～ ';
                    }
                    break;
                case 'before':
                    if (!empty($request->conditions['after']) || !empty($request->conditions['before'])) {
                        $conditions['term'] .= empty($request->conditions['before']) ? 'なし': date('Y/m/d', strtotime($request->conditions['before']));
                    }
                    break;
                default:
                    if (!empty($val)) {
                        $conditions[$key] = $val;
                    }
                    break;
            }
        }
        Log::debug($conditions);
        return response()->json([
            'orders' => $orders,
            'conditions' => $conditions
        ]);
    }

    public function payment(Request $request) {
        Order::where('id', $request->order_id)->update([
            'payment' => $request->payment,
            'paid_at' => date('Y-m-d H:i:s')
        ]);

        return response()->json([
            'ok' => true
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

    private function addTag($tag) {
        if ($tag >= 10999) {
            $tag = 1000;
        } else {
            $tag++;
        }

        return $tag;
    }
}
