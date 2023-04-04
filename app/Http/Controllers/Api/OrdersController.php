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
use App\Models\Ipaddress;
use App\Services\Utility;

class OrdersController extends Controller
{
    const CONDITION_KEY_JP = [
        'order_id' => '伝票番号',
        'term' => '期間',
        'tag' => 'タグ',
        'has_paid' => 'お支払い済み',
        'has_handed' => 'お渡し済み',
    ];
    const CONDITION_VALUE_JP = [
        'neither' => '指定しない',
        'paid' => 'はい',
        'unpaid' => 'いいえ',
        'handed' => 'はい',
        'unhanded' => 'いいえ',
    ];

    public function store(Request $request) {
        if (!empty($request->created_at)) {
            $paid_at = date('Y-m-d 00:00:00', strtotime($request->created_at));
        } else {
            $paid_at = date('Y-m-d H:i:s');
        }
        $invoice_id = null;
        $is_invoice = (boolean)$request->invoice;
        if ($is_invoice) {
            $cutoff_date = Customer::where('id', $request->customer_id)->value('cutoff_date');
            list($period_start, $period_end) = Utility::searchInvoicePeriod($cutoff_date, $request->created_at);
            // 入金確認が必要なお客様は「paid_at」を埋めない
            if ((boolean)Customer::where('id', $request->customer_id)->value('needs_payment_confimation')) {
                $paid_at = $period_end;
            } else {
                $paid_at = null;
            }

            if (!$request->check_return) {
                // 返却確認しない場合は本日付けで伝票を計上
                $model = new Invoice();
                $invoice_id = $model->existsTargetInvoice($request->customer_id, $period_start, $period_end);
                if (!is_null($invoice_id)) {
                    $invoice = Invoice::find($invoice_id);
                } else {
                    $invoice = Invoice::query()->create([
                        'manager_id' => $request->manager_id,
                        'customer_id' => $request->customer_id,
                        'period_start' => $period_start,
                        'period_end' => $period_end,
                        'paid_at' => $paid_at,
                    ]);
                    $invoice_id = $invoice->id;
                }
            }
        } else {
            // 未収の場合は支払い日時をnullに
            if ($request->not_paid) {
                $paid_at = null;
            }
        }

        // 注文レコード追加
        $order_record = [
            'store_id' => Auth::id(),
            'manager_id' => $request->manager_id,
            'customer_id' => $request->customer_id,
            'is_invoice' => $is_invoice,
            'amount' => $request->amount,
            'reduction' => $request->reduction,
            'discount' => $request->discount,
            'payment' => $request->payment,
            'paid_at' => $paid_at,
        ];
        // 預り日設定
        if (!empty($request->created_at)) {
            $order_record['created_at'] = $request->created_at;
        }
        // 返却日設定
        if (!$request->check_return) {
            $order_record['handed_at'] = date('Y-m-d H:i:s');
            $order_record['invoice_id'] = $invoice_id;
        }
        $order = Order::query()->create($order_record);

        $tag = TagNumber::where('manager_id', $request->manager_id)->value('tag_number');
        $response = [];
        // foreach ($request->order as $clothes_id => $count) {
        foreach ($request->indexes as $clothes_id) {
            $tag_count = Clothes::where('id', $clothes_id)->value('tag_count');
            for ($i = 1; $i <= $request->order[$clothes_id]; $i++) {
                if (!in_array($clothes_id, $request->dont_issue_tag_list)) {
                    // タグを発行する場合
                    $converted_tag = Utility::convertTagFormat($tag);
                    OrderClothes::query()->create([
                        'order_id' => $order->id,
                        'clothes_id' => $clothes_id,
                        'tag' => $converted_tag,
                        'handed_at' => ($request->check_return) ? null : date('Y-m-d H:i:s'),
                    ]);

                    if ($tag_count > 1) {
                        for ($j = 1; $j < $tag_count; $j++) {
                            $tag = $this->addTag($tag);
                            $converted_tag = Utility::convertTagFormat($tag);
                            OrderClothes::query()->create([
                                'order_id' => $order->id,
                                'clothes_id' => 999,
                                'tag' => $converted_tag,
                                'handed_at' => ($request->check_return) ? null : date('Y-m-d H:i:s'),
                            ]);
                        }
                    } else {
                        if (!isset($response[$clothes_id])) {
                            $response[$clothes_id] = $converted_tag;
                        } else if ($i >= 2 && $i === $request->order[$clothes_id]) {
                            $response[$clothes_id] .= '～' . $converted_tag;
                        }
                    }

                    $tag = $this->addTag($tag);
                } else {
                    // タグを発行しない場合
                    OrderClothes::query()->create([
                        'order_id' => $order->id,
                        'clothes_id' => $clothes_id,
                        'tag' => '0-000',
                        'handed_at' => ($request->check_return) ? null : date('Y-m-d H:i:s'),
                    ]);

                    if ($tag_count > 1) {
                        for ($j = 1; $j < $tag_count; $j++) {
                            OrderClothes::query()->create([
                                'order_id' => $order->id,
                                'clothes_id' => 999,
                                'tag' => '0-000',
                                'handed_at' => ($request->check_return) ? null : date('Y-m-d H:i:s'),
                            ]);
                        }
                    } else {
                        if (!isset($response[$clothes_id])) {
                            $response[$clothes_id] = '0-000';
                        } else if ($i >= 2 && $i === $request->order[$clothes_id]) {
                            $response[$clothes_id] .= '～0-000';
                        }
                    }
                }
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
                        $conditions[$this::CONDITION_KEY_JP[$key]] = $this::CONDITION_VALUE_JP[$val];
                    }
                    break;
                case 'after':
                    if (!empty($request->conditions['after']) || !empty($request->conditions['before'])) {
                        $conditions['期間'] = empty($request->conditions['after']) ? 'なし ～ ': date('Y/m/d', strtotime($request->conditions['after'])). ' ～ ';
                    }
                    break;
                case 'before':
                    if (!empty($request->conditions['after']) || !empty($request->conditions['before'])) {
                        $conditions['期間'] .= empty($request->conditions['before']) ? 'なし': date('Y/m/d', strtotime($request->conditions['before']));
                    }
                    break;
                default:
                    if (!empty($val)) {
                        $conditions[$this::CONDITION_KEY_JP[$key]] = $val;
                    }
                    break;
            }
        }

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

    public function delete(Request $request) {
        $order = Order::find($request->order_id);
        $invoice_id = $order->invoice_id;

        $customer = Customer::find($request->customer_id);
        $customer->decrement('total_sales', $order->amount);
        $customer->decrement('number_of_visits');
        $order->delete();

        if (!is_null($invoice_id) && !Order::where('invoice_id', $invoice_id)->exists()) {
            Invoice::find($invoice_id)->delete();
        }

        return response()->json([
            'ok' => true
        ]);
    }

    public function updateTag(Request $request) {
        Log::debug($request->tag);
        $model = OrderClothes::find($request->id);
        $model->tag = $request->tag;
        $model->save();

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
        $ip_address = Ipaddress::where('id', 1)->value('ipaddress');

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
            'is_invoice'         => $order->is_invoice,
            'ip_address'         => $ip_address,
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
