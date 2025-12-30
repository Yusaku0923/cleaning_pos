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

    public function order_clothes() {
        return $this->hasMany(OrderClothes::class, 'order_id');
    }

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

    public function fetchOrders($customer_id = null, $limit = 20, $where = [])
    {
        $query = Order::query();
        if (!is_null($customer_id)) {
            $query->where('customer_id', $customer_id);
        }
        if (!empty($where)) {
            // period
            if (!empty($where['after'])) {
                $query->where('created_at', '>=', date('Y/m/d 00:00:00', strtotime($where['after'])));
            }
            if (!empty($where['before'])) {
                $query->where('created_at', '<=', date('Y/m/d 23:59:59', strtotime($where['before'])));
            }
            // has paid
            if (!empty($where['has_paid']) && $where['has_paid'] !== 'neither') {
                if ($where['has_paid'] === 'paid') {
                    $query->whereNotNull('paid_at');
                } else if ($where['has_paid'] === 'unpaid') {
                    $query->whereNull('paid_at');
                }
            }
            // has handed
            if (!empty($where['has_handed']) && $where['has_handed'] !== 'neither') {
                if ($where['has_handed'] === 'handed') {
                    $query->whereNotNull('handed_at');
                } else if ($where['has_handed'] === 'unhanded') {
                    $query->whereNull('handed_at');
                }
            }
            // id
            if (!empty($where['order_id'])) {
                $query->where('id', $where['order_id']);
            }
            // has specified tag item
            if (!empty($where['tag'])) {
                Log::debug($where['tag']);
                $ids = $this->hasSpecifiedTag($customer_id, $where['tag']);
                Log::debug($ids);
                if (is_null($ids)) {
                    return [];
                } else {
                    $query->whereIn('id', $ids);
                }
            }
        }
        if ($limit > 0) {
            $query->limit($limit);
        }
        $query->orderBy('orders.created_at', 'desc');
        $orders = $query->get()->toArray();

        foreach ($orders as $key => $array) {
            $query = OrderClothes::query();
            $query->select('order_clothes.*', 'clothes.name', 'clothes.name_kana', 'clothes.price', 'clothes.tag_count');
            $query->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id');
            $query->where('order_clothes.order_id', $array['id']);
            $query->orderBy('order_clothes.id', 'asc');
            $orders[$key]['customer_name'] = Customer::where('id', $array['customer_id'])->value('name');
            $orders[$key]['items'] = $query->get()->toArray();
            $orders[$key]['count'] = OrderClothes::where('order_id', $array['id'])->count();
        }

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

    public function fetchDailyOrders($manager_id, $date) {
        $orders = Order::select('orders.*', 'customers.name')
                        ->join('customers', 'orders.customer_id', '=', 'customers.id')
                        ->where('orders.created_at', '>=', $date . ' 00:00:00')
                        ->where('orders.created_at', '<=', $date . ' 23:59:59')
                        ->where('orders.manager_id', $manager_id)
                        ->orderBy('orders.created_at', 'asc')
                        ->get()->toArray();

        $daily_sum = Order::where('orders.created_at', '>=', $date . ' 00:00:00')
                        ->where('orders.created_at', '<=', $date . ' 23:59:59')
                        ->where('orders.manager_id', $manager_id)
                        ->sum('amount');

        $month_start = date('Y-m-01', strtotime($date));
        $month_end = date('Y-m-t', strtotime($date));
        $monthly_sum = Order::where('orders.created_at', '>=', $month_start . ' 00:00:00')
                            ->where('orders.created_at', '<=', $month_end . ' 23:59:59')
                            ->where('orders.manager_id', $manager_id)
                            ->sum('amount');

        if (!empty($orders)) {
            foreach ($orders as $key => $order) {
                $item = OrderClothes::select('order_clothes.*', 'clothes.id as clothes_id', 'clothes.name', 'clothes.price')
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
        
        // タグを数値として解釈してソートする関数
        usort($list, function($a, $b) {
            return $this->compareTags($a['tag'], $b['tag']);
        });
        
        // 統合せず、個別に表示するが、clothes_id=999の場合は前のレコードと結合
        $i = 0;
        while ($i < count($list)) {
            $row = $list[$i];
            
            // clothes_id=999（スリーピースの追加パーツ）の場合は、前のレコードと結合
            if ($row['clothes_id'] == 999 && count($result) > 0) {
                $lastIndex = count($result) - 1;
                // 前のレコードのtag_endを更新
                if ($this->compareTags($row['tag'], $result[$lastIndex]['tag_end']) > 0) {
                    $result[$lastIndex]['tag_end'] = $row['tag'];
                }
            } else {
                // 通常のレコードは個別に追加
                $result[] = [
                    'id' => $row['clothes_id'],
                    'name' => $row['name_kana'],
                    'tag_start' => $row['tag'],
                    'tag_end' => $row['tag'],
                    'price' => $row['price'],
                    'count' => 1,
                ];
            }
            
            $total_count++;
            $i++;
        }

        return [$result, $total_count];
    }

    /**
     * タグを比較する（例：8-873 と 8-874 を比較）
     * 9-999が最大で、次は0-001に戻るルールを考慮
     * 戻り値: 負の値（$tagA < $tagB）、0（等しい）、正の値（$tagA > $tagB）
     */
    private function compareTags($tagA, $tagB) {
        // タグが数値形式（例：8873）の場合は文字列形式に変換
        if (is_numeric($tagA)) {
            $tagA = $this->formatTagFromNumber($tagA);
        }
        if (is_numeric($tagB)) {
            $tagB = $this->formatTagFromNumber($tagB);
        }
        
        // タグを分割（例：8-873 → [8, 873]）
        $partsA = explode('-', $tagA);
        $partsB = explode('-', $tagB);
        
        // フォーマットが異なる場合は文字列として比較
        if (count($partsA) !== 2 || count($partsB) !== 2) {
            return strcmp($tagA, $tagB);
        }
        
        // 最初の部分（例：8）を比較
        $firstA = (int)$partsA[0];
        $firstB = (int)$partsB[0];
        
        // 2番目の部分（例：873）を比較
        $secondA = (int)$partsA[1];
        $secondB = (int)$partsB[1];
        
        // 9-999が最大で、次は0-001に戻るルールを考慮
        // 0-001は9-999の直後に来るようにする
        $isMaxA = ($firstA === 9 && $secondA === 999);
        $isMaxB = ($firstB === 9 && $secondB === 999);
        $isMinA = ($firstA === 0 && $secondA === 1);
        $isMinB = ($firstB === 0 && $secondB === 1);
        
        // 両方が最大値または最小値の場合
        if ($isMaxA && $isMaxB) {
            return 0;
        }
        if ($isMinA && $isMinB) {
            return 0;
        }
        
        // 片方が最大値、もう片方が最小値の場合
        if ($isMaxA && $isMinB) {
            return -1; // 9-999が先、0-001が後
        }
        if ($isMinA && $isMaxB) {
            return 1; // 9-999が先、0-001が後
        }
        
        // 片方が最大値の場合、最大値が最後に来る（ただし0-001よりは前）
        if ($isMaxA && !$isMinB) {
            return 1;
        }
        if ($isMaxB && !$isMinA) {
            return -1;
        }
        
        // 片方が最小値の場合、最小値は最大値の直後に来る
        // 0-001は9-999の後に来るが、他のタグ（1-000以上）の前には来ない
        if ($isMinA) {
            // 比較対象が最大値の場合は、最小値が後
            if ($isMaxB) {
                return 1;
            }
            // 比較対象が1-000以上の場合は、最小値が前（0-001は1-000より前）
            if ($firstB >= 1) {
                return -1;
            }
        }
        if ($isMinB) {
            // 比較対象が最大値の場合は、最小値が後
            if ($isMaxA) {
                return -1;
            }
            // 比較対象が1-000以上の場合は、最小値が前（0-001は1-000より前）
            if ($firstA >= 1) {
                return 1;
            }
        }
        
        // 通常の比較
        if ($firstA !== $firstB) {
            return $firstA <=> $firstB;
        }
        
        return $secondA <=> $secondB;
    }
    
    /**
     * 数値タグ（例：8873）を文字列形式（例：8-873）に変換
     */
    private function formatTagFromNumber($tag) {
        if ($tag >= 10000) {
            $first = substr((string)$tag, 0, 2);
            $second = (int)substr((string)$tag, 2);
        } else {
            $first = substr((string)$tag, 0, 1);
            $second = (int)substr((string)$tag, 1);
        }
        return $first . '-' . str_pad((string)$second, 3, '0', STR_PAD_LEFT);
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
                        ->orderBy('created_at', 'desc')
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
        if (!is_null($customer_id)) {
            $query->where('orders.customer_id', $customer_id);
        }
        $query->where('order_clothes.tag', $tag);
        Log::debug($query->toSql());

        return $query->get('id')->toArray();
    }
}
