<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Clothes;
use App\Models\Customer;
use App\Models\Manager;
use App\Models\Setting;
use App\Models\TagCounter;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** 旧 OrdersController::create (預り入力)。担当者・顧客は URL (?m=&c=) から。 */
class OrderController extends Controller
{
    public function create(Request $request)
    {
        /** @var Manager|null $manager */
        $manager = $request->attributes->get('manager');
        /** @var Customer|null $customer */
        $customer = $request->attributes->get('customer');
        if ($manager === null || $customer === null) {
            return redirect(pos_url('home'));
        }

        // 旧はここ (GET) で顧客表示へ {event: 'order', name} を送っていた。v2 は GET では送らない。
        // 画面側 (OrderComponent の mounted) の TODO(Task 7) から POST で送る。

        // 旧と同じくカテゴリ ID 1 (「一時」) は出さない
        $categories = Category::with(['clothes' => fn ($q) => $q->orderBy('sort_key', 'asc')])
            ->where('id', '!=', 1)
            ->get();

        return view('orders.create')->with([
            'title' => '預　り　入　力',
            'manager_id' => $manager->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name ?? '',
            'is_invoice' => (bool) $customer->is_invoice,
            'check_return' => (bool) $customer->needs_return_confirmation,
            'latest_tag' => (string) (TagCounter::where('manager_id', $manager->id)->value('value') ?? '1000'),
            'list' => $categories,
            'often_ordered' => $this->oftenOrdered($customer),
            'tax_rate' => (int) (Tax::query()->value('tax') ?? 10),
            'tax_rounding' => config('pos.tax_rounding.invoice'),
            'printer_ip' => Setting::get('printer_ip'),
        ]);
    }

    /** 旧 Clothes::fetchOftenOrdered: この顧客がよく注文する商品 (回数の多い順。削除済みの商品と 999 は除く)。 */
    private function oftenOrdered(Customer $customer): array
    {
        $counts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.customer_id', $customer->id)
            ->where('order_items.clothes_id', '<>', Clothes::MULTI_TAG_CLOTHES_ID)
            ->whereNull('order_items.deleted_at')
            ->groupBy('order_items.clothes_id')
            ->selectRaw('order_items.clothes_id, COUNT(order_items.clothes_id) AS count');

        return Clothes::query()
            ->joinSub($counts, 'often', 'often.clothes_id', '=', 'clothes.id')
            ->select('clothes.*', 'often.count')
            ->orderByDesc('often.count')
            ->orderBy('clothes.id')
            ->get()
            ->toArray();
    }
}
