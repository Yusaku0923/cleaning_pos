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

    public function fetchOrder($customer_id)
    {
        /**
         * $orders = [
         *     'items': [
         *          [
         *               'clothes.(order_clothes).name',
         *               'order_clothes.tags': [],
         *          ]
         *     ]
         *     'order.id',
         *     'COUNT(order.(order_clothes).id) AS total_count',
         *     'order.amount',
         *     'order.discount',
         *     'order.payment',
         *     'order.is_registered_as_invoice',
         *     'order.paid_at',
         *     'order.is_handed_over',
         *     'order.note',
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

    public function fetchReciptDetail($order_id) {
        $result = [];
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
        }

        return $result;
    }
}
