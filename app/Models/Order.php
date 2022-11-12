<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function fetchOrders($customer_id)
    {
        /**
         * $orders = [
         *     [
         *         'items': [
         *              [
         *                   'clothes.(order_clothes).name',
         *                   'order_clothes.tags': [],
         *              ]
         *         ]
         *         'order.id',
         *         'COUNT(order.(order_clothes).id) AS total_count',
         *         'order.amount',
         *         'order.discount',
         *         'order.payment',
         *         'order.is_registered_as_invoice',
         *         'order.paid_at',
         *         'order.is_handed_over',
         *         'order.note',
         *    ],
         *   ...
         * ]
         * 
         * ※orderBy DESC created_at
         */

        
        $orders = Order::where('customer_id', $customer_id)
                        ->orderBy('created_at', 'desc')
                        ->get()->toArray();

        if (empty($orders)) {
            return [];
        }

        foreach ($orders as $key => $order) {
            $orders[$key]['total_count'] = OrderClothes::where('order_id', $order['id'])->count();
            $clothes = OrderClothes::select('clothes_id')
                                    ->where('order_id', $order['id'])
                                    ->groupBy('clothes_id')
                                    ->get()->toArray();
            $clothes = array_map(function ($array) {
                return $array['clothes_id'];
            }, $clothes);
            foreach ($clothes as $id) {
                $item['name'] = Clothes::where('id', $id)->value('name');
                $tags = OrderClothes::select('tag')
                                    ->where('order_id', $order['id'])
                                    ->where('clothes_id', $id)
                                    ->get()->toArray();
                
                $item['tags'] = array_map(function ($array) {
                    return $array['tag'];
                }, $tags);

                $orders[$key]['items'][] = $item;
            }
        }

        return $orders;
    }

    public function fetchDailyOrders($date) {
        $orders = Order::select('orders.*', 'customers.name')
                        ->join('customers', 'orders.customer_id', '=', 'customers.id')
                        ->where('orders.created_at', '>=', $date . ' 00:00:00')
                        ->where('orders.created_at', '<=', $date . ' 23:59:59')
                        ->orderBy('orders.created_at', 'asc')
                        ->get()->toArray();

        $daily_sum = Order::where('orders.created_at', '>=', $date . ' 00:00:00')
                        ->where('orders.created_at', '<=', $date . ' 23:59:59')
                        ->sum('amount');

        $month_start = date('Y-m-01', strtotime($date));
        $month_end = date('Y-m-t', strtotime($date));
        $monthly_sum = Order::where('orders.created_at', '>=', $month_start . ' 00:00:00')
                            ->where('orders.created_at', '<=', $month_end . ' 23:59:59')
                            ->sum('amount');

        if (!empty($orders)) {
            foreach ($orders as $key => $order) {
                $item = OrderClothes::select('order_clothes.*', 'clothes.name', 'clothes.price')
                                    ->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id')
                                    ->where('order_id', $order['id'])
                                    ->get()->toArray();
                $orders[$key]['items'] = $item;
            }
        } else {
            $orders = [];
        }

        return [$orders, $daily_sum, $monthly_sum];
    }

    public function fetchReciptDetail($order_id) {
        $result = [];
        $total_count = 0;
        $list = OrderClothes::join('orders', 'order_clothes.order_id', '=', 'orders.id')
                            ->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id')
                            ->where('order_id', $order_id)
                            ->get()->toArray();
        foreach ($list as $row) {
            $index = array_search($row['clothes_id'], array_column($result, 'id'));
            if ($index === false) {
                $result[] = [
                    'id' => $row['clothes_id'],
                    'name' => $row['name'],
                    'tag_start' => $row['tag'],
                    'tag_end' => $row['tag'],
                    'price' => $row['price'],
                    'count' => 1,
                ];
            } else {
                $result[$index]['count']++;
                $result[$index]['tag_end'] = $row['tag'];
            }
            $total_count++;
        }

        return [$result, $total_count];
    }

    public function fetchUnhandedOrders($customer_id) {
        $orders = Order::where('customer_id', $customer_id)
                        ->whereNull('handed_at')
                        ->orderBy('created_at', 'asc')
                        ->get()->toArray();
        
        if (empty($orders)) {
            return [];
        }

        foreach ($orders as $key => $order) {
            $items = OrderClothes::select('order_clothes.*', 'clothes.name', 'clothes.price')
                                ->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id')
                                ->where('order_clothes.order_id', $order['id'])
                                ->get()->toArray();
            $orders[$key]['created_at'] = date('Y/m/d', strtotime($order['created_at']));
            $orders[$key]['items'] = $items;
        }

        return $orders;
    }
}
