<?php

namespace App\Http\Controllers;

use App\Domain\Tag\TagNumber;
use App\Models\Customer;
use App\Models\CustomerInformation;
use App\Models\Manager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TagCounter;
use Illuminate\Http\Request;

/** 旧 HomeController。担当者・顧客は ResolvePosContext が URL から読んだものを使う。 */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $ua = $request->header('User-Agent', '');
        if (preg_match('/iPhone|Android.*Mobile/i', $ua)) {
            return redirect()->route('delivery.sp.choice');
        }

        /** @var Manager|null $manager */
        $manager = $request->attributes->get('manager');
        /** @var Customer|null $customer */
        $customer = $request->attributes->get('customer');

        $managers = Manager::query()->get();
        if ($manager !== null) {
            $tag = TagCounter::where('manager_id', $manager->id)->value('value');
            $latest_order = $this->fetchLatestOrder($manager->id);
        }
        if ($customer !== null) {
            $orders = $this->fetchOrders($customer, 4);
            $unpaid_amount = Order::where('customer_id', $customer->id)
                ->whereNull('paid_at')
                ->sum('amount');
            $latest_visit = Order::where('customer_id', $customer->id)
                ->max('created_at');
            $info = CustomerInformation::where('customer_id', $customer->id)
                ->orderBy('created_at', 'asc')
                ->get()->toArray();
        }

        return view('home')->with([
            'title' => '顧　客　呼　出',
            'managers' => $managers,
            'customer' => $customer ?? [],
            'customer_info' => $info ?? [],
            'orders' => $orders ?? [],
            'tag' => $tag ?? null,
            'tag_label' => isset($tag) ? self::formatTag((int) $tag) : null,
            'latest_order' => $latest_order ?? [],
            'unpaid_amount' => $unpaid_amount ?? null,
            'latest_visit' => $latest_visit ?? null,
        ]);
    }

    public function menu()
    {
        return view('menu')->with([
            'title' => 'マスタ編集メニュー',
        ]);
    }

    /** 旧 Utility::convertTagFormat。移行データに範囲外の値があっても画面は落とさない。 */
    private static function formatTag(int $tag): string
    {
        try {
            return TagNumber::fromInt($tag)->toString();
        } catch (\InvalidArgumentException) {
            return intdiv($tag, 1000).'-'.str_pad((string) ($tag % 1000), 3, '0', STR_PAD_LEFT);
        }
    }

    /** 旧 Order::fetchLatestOrder と同じ形 (直前預り取り消しモーダル用)。 */
    private function fetchLatestOrder(int $managerId): array
    {
        $order = Order::where('manager_id', $managerId)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($order === null) {
            return [];
        }

        $order = $order->toArray();
        $order['items'] = OrderItem::select('order_items.*', 'clothes.name', 'clothes.price')
            ->join('clothes', 'order_items.clothes_id', '=', 'clothes.id')
            ->where('order_items.order_id', $order['id'])
            ->get()->toArray();

        return $order;
    }

    /** 旧 Order::fetchOrders($customer_id, $limit) と同じ形 (来店履歴カード用)。 */
    private function fetchOrders(Customer $customer, int $limit): array
    {
        $orders = Order::where('customer_id', $customer->id)
            ->orderBy('orders.created_at', 'desc')
            ->limit($limit)
            ->get()->toArray();

        foreach ($orders as $key => $array) {
            $orders[$key]['customer_name'] = $customer->name;
            $orders[$key]['items'] = OrderItem::select('order_items.*', 'clothes.name', 'clothes.name_kana', 'clothes.price', 'clothes.tag_count')
                ->join('clothes', 'order_items.clothes_id', '=', 'clothes.id')
                ->where('order_items.order_id', $array['id'])
                ->orderBy('order_items.id', 'asc')
                ->get()->toArray();
            $orders[$key]['count'] = count($orders[$key]['items']);
        }

        return $orders;
    }
}
