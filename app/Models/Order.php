<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    // public function order_clothes() {
    //     return $this->hasMany(OrderClothes::class, 'order_id');
    // }

    public function clothes(): BelongsToMany {
        return $this->belongsToMany(Clothes::class)
                    ->using(OrderClothes::class)->withPivot(['clothes_id']);
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($user) {
            $user->order_clothes()->delete();
        });
    }

    public function fetchOrders($customer_id, $limit = 20, $where = [])
    {
        $query = Order::query();
        $query->where('customer_id', $customer_id);
        if (!empty($where)) {
            // period
            if ($where['before'] !== '') {
                $query->where('created_at', '<=', $where['before']);
            }
            if ($where['after'] !== '') {
                $query->where('created_at', '>=', $where['after']);
            }
            // has paid
            if ($where['has_paid'] !== '') {
                if ($where['has_paid']) {
                    $query->whereNotNull('paid_at');
                } else {
                    $query->whereNull('paid_at');
                }
            }
            // has handed
            if ($where['has_handed'] !== '') {
                if ($where['has_handed']) {
                    $query->whereNotNull('handed_at');
                } else {
                    $query->whereNull('handed_at');
                }
            }
            // id
            if ($where['order_id'] !== '') {
                $query->where('id', $where['order_id']);
            }
            // has specified tag item
            if ($where['tag'] !== '') {
                $order_id = $this->hasSpecifiedTag($customer_id, $where['tag']);
                if (is_null($order_id)) {
                    return [];
                } else {
                    $query->where('id', $order_id);
                }
            }
        }
        $query->limit($limit);
        $query->orderBy('orders.created_at', 'desc');
        $orders = $query->get()->toArray();

        foreach ($orders as $key => $array) {
            $query = OrderClothes::query();
            $query->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id');
            $query->where('order_clothes.order_id', $array['id']);
            $orders[$key]['items'] = $query->get()->toArray();
            $orders[$key]['count'] = OrderClothes::where('order_id', $array['id'])->count();
        }

        dd($orders);
        return $orders;
    }

    public function fetchLatestOrder($manager_id) {
        $order = Order::where('manager_id', $manager_id)
                        ->orderBy('created_at', 'desc')
                        ->limit(1)
                        ->get()->toArray();
        if (empty($order)) {
            return [];
        } else {
            $order = $order[0];
        }

        $items = OrderClothes::select('order_clothes.*', 'clothes.name', 'clothes.price')
                            ->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id')
                            ->where('order_clothes.order_id', $order['id'])
                            ->get()->toArray();
        $order['items'] = $items;

        return $order;
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
                    'name' => $row['name_kana'],
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

    public function fetchUnpaidOrders($customer_id) {
        $orders = Order::where('customer_id', $customer_id)
                        ->whereNull('invoice_id')
                        ->whereNull('paid_at')
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

    public function hasSpecifiedTag($customer_id, $tag) {
        $query = OrderClothes::select('orders.id');
        $query->join('orders', 'order_clothes.order_id', '=', 'orders.id');
        $query->where('orders.customer_id', $customer_id);
        $query->where('order_clothes.tag', $tag);

        return $query->value('id');
    }
}
