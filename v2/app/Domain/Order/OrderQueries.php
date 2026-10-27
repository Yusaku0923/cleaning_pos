<?php

namespace App\Domain\Order;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * 画面に渡す注文の一覧 (旧 Order::fetchOrders / fetchUnpaidOrders / fetchUnhandedOrders / hasSpecifiedTag)。
 * 返す配列の形 (キー・並び) は旧と同じ。店舗は Order の BelongsToStore で絞られる (旧は全店舗を見ていた)。
 * 品物は注文ごとに 1 回ずつ読まず、まとめて読む (旧は注文 1 件につき 3 回の問い合わせ)。
 */
class OrderQueries
{
    /**
     * 旧 fetchOrders($customer_id, $limit, $where)。$customerId null = 全顧客 (全体検索)。$limit <= 0 は無制限。
     * $where のキー: after, before (Y-m-d), has_paid (paid|unpaid|neither), has_handed (handed|unhanded|neither), order_id, tag。
     */
    public function fetchOrders(?int $customerId = null, int $limit = 20, array $where = []): array
    {
        $query = Order::query();
        if ($customerId !== null) {
            $query->where('customer_id', $customerId);
        }
        if (! empty($where['after'])) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($where['after'])));
        }
        if (! empty($where['before'])) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($where['before'])));
        }
        match ($where['has_paid'] ?? null) {
            'paid' => $query->whereNotNull('paid_at'),
            'unpaid' => $query->whereNull('paid_at'),
            default => null,
        };
        match ($where['has_handed'] ?? null) {
            'handed' => $query->whereNotNull('handed_at'),
            'unhanded' => $query->whereNull('handed_at'),
            default => null,
        };
        if (! empty($where['order_id'])) {
            $query->where('id', $where['order_id']);
        }
        if (! empty($where['tag'])) {
            $query->whereIn('id', $this->hasSpecifiedTag($customerId, (string) $where['tag']));
        }
        if ($limit > 0) {
            $query->limit($limit);
        }
        $orders = $query->orderBy('orders.created_at', 'desc')->orderBy('orders.id', 'desc')->get()->toArray();

        $items = $this->itemsFor(array_column($orders, 'id'), withKana: true);
        $names = Customer::whereIn('id', array_unique(array_column($orders, 'customer_id')))->pluck('name', 'id');
        foreach ($orders as $key => $order) {
            $orders[$key]['customer_name'] = $names[$order['customer_id']] ?? null;
            $orders[$key]['items'] = $items[$order['id']] ?? [];
            $orders[$key]['count'] = count($orders[$key]['items']);
        }

        return $orders;
    }

    /** 旧 fetchUnpaidOrders: 請求書に載っていない未収の注文 (古い順)。預り日は Y/m/d。 */
    public function fetchUnpaidOrders(int $customerId): array
    {
        return $this->withItems(
            Order::where('customer_id', $customerId)->whereNull('invoice_id')->whereNull('paid_at')->orderBy('created_at', 'asc')->orderBy('id', 'asc')
        );
    }

    /** 旧 fetchUnhandedOrders: 未渡しの注文 (新しい順)。預り日は Y/m/d。 */
    public function fetchUnhandedOrders(int $customerId): array
    {
        return $this->withItems(
            Order::where('customer_id', $customerId)->whereNull('handed_at')->orderBy('created_at', 'desc')->orderBy('id', 'desc')
        );
    }

    /** 旧 hasSpecifiedTag: そのタグの品物を含む注文の ID。 */
    public function hasSpecifiedTag(?int $customerId, string $tag): array
    {
        $query = OrderItem::query()->select('orders.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.tag', $tag);
        if ($customerId !== null) {
            $query->where('orders.customer_id', $customerId);
        }

        return $query->pluck('orders.id')->all();
    }

    private function withItems(Builder $query): array
    {
        $orders = $query->get()->toArray();
        $items = $this->itemsFor(array_column($orders, 'id'), withKana: false);
        foreach ($orders as $key => $order) {
            $orders[$key]['created_at'] = date('Y/m/d', strtotime($order['created_at']));
            $orders[$key]['items'] = $items[$order['id']] ?? [];
        }

        return $orders;
    }

    /** 注文 ID ごとの品物 (旧: order_clothes.* + 商品名・価格。商品は削除済みでも出す) */
    private function itemsFor(array $orderIds, bool $withKana): array
    {
        if ($orderIds === []) {
            return [];
        }
        $columns = $withKana
            ? ['order_items.*', 'clothes.name', 'clothes.name_kana', 'clothes.price', 'clothes.tag_count']
            : ['order_items.*', 'clothes.name', 'clothes.price'];

        return OrderItem::query()->select($columns)
            ->join('clothes', 'order_items.clothes_id', '=', 'clothes.id')
            ->whereIn('order_items.order_id', $orderIds)
            ->orderBy('order_items.id', 'asc')
            ->get()
            ->groupBy('order_id')
            ->map(fn ($rows) => $rows->toArray())
            ->all();
    }
}
