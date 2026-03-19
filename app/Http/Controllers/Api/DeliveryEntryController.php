<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCustomer;
use App\Models\DeliveryDailyEntry;
use Illuminate\Http\Request;

class DeliveryEntryController extends Controller
{
    /**
     * 顧客一覧（部署・商品付き）
     */
    public function customers()
    {
        $customers = DeliveryCustomer::with(['departments.products'])->get();
        return response()->json(['customers' => $customers]);
    }

    /**
     * 顧客の部署・商品一覧
     */
    public function departments(int $id)
    {
        $customer = DeliveryCustomer::with(['departments.products'])->findOrFail($id);
        return response()->json(['departments' => $customer->departments]);
    }

    /**
     * 日次入力一覧取得（顧客ID・日付でフィルタ）
     */
    public function index(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:delivery_customers,id',
            'date' => 'required|date',
        ]);

        $entries = DeliveryDailyEntry::whereHas('product.department', function ($q) use ($request) {
            $q->where('delivery_customer_id', $request->customer_id);
        })->where('date', $request->date)->with('product.department')->get();

        return response()->json(['entries' => $entries]);
    }

    /**
     * 日次入力保存（upsert）
     */
    public function store(Request $request)
    {
        $request->validate([
            'entries' => 'required|array',
            'entries.*.delivery_product_id' => 'required|exists:delivery_products,id',
            'entries.*.date' => 'required|date',
            'entries.*.quantity' => 'required|integer|min:0',
        ]);

        foreach ($request->entries as $entry) {
            DeliveryDailyEntry::updateOrCreate(
                [
                    'delivery_product_id' => $entry['delivery_product_id'],
                    'date' => $entry['date'],
                ],
                [
                    'quantity' => $entry['quantity'],
                    'note' => $entry['note'] ?? null,
                ]
            );
        }

        return response()->json(['ok' => true]);
    }

    /**
     * 顧客の入力済み日付一覧（ステータス付き）直近60日分
     */
    public function entryStatus(Request $request)
    {
        $request->validate(['customer_id' => 'required|exists:delivery_customers,id']);

        $entries = DeliveryDailyEntry::whereHas('product.department', function ($q) use ($request) {
            $q->where('delivery_customer_id', $request->customer_id);
        })
        ->where('date', '>=', now()->subDays(60)->toDateString())
        ->where('quantity', '>', 0)
        ->with('deliveryNote')
        ->get()
        ->groupBy(fn($e) => $e->date->toDateString())
        ->map(fn($group) => [
            'date' => $group->first()->date->toDateString(),
            'finalized' => $group->some(fn($e) => !is_null($e->delivery_note_id)),
        ])
        ->values();

        return response()->json(['dates' => $entries]);
    }

    /**
     * 日次入力削除（顧客ID・日付で一括削除）
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:delivery_customers,id',
            'date' => 'required|date',
        ]);

        DeliveryDailyEntry::whereHas('product.department', function ($q) use ($request) {
            $q->where('delivery_customer_id', $request->customer_id);
        })->where('date', $request->date)->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * 集計プレビュー（iPad管理画面用）
     */
    public function preview(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:delivery_customers,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
        ]);

        $customer = DeliveryCustomer::with('departments.products')->findOrFail($request->customer_id);

        $entries = [];
        $hasFinalized = false;

        foreach ($customer->departments as $dept) {
            $deptData = ['name' => $dept->name, 'products' => [], 'subtotal' => 0];
            foreach ($dept->products as $product) {
                $entryRows = DeliveryDailyEntry::where('delivery_product_id', $product->id)
                    ->whereBetween('date', [$request->period_start, $request->period_end])
                    ->get();
                $qty = $entryRows->sum('quantity');
                if ($qty > 0) {
                    if ($entryRows->some(fn($e) => !is_null($e->delivery_note_id))) {
                        $hasFinalized = true;
                    }
                    $amount = $qty * $product->unit_price;
                    $deptData['products'][] = [
                        'name' => $product->name,
                        'quantity' => $qty,
                        'unit_price' => $product->unit_price,
                        'amount' => $amount,
                        'tax_rate' => $product->tax_rate,
                    ];
                    $deptData['subtotal'] += $amount;
                }
            }
            if (!empty($deptData['products'])) {
                $entries[] = $deptData;
            }
        }

        // 消費税計算（税率グループごと・切り捨て）
        $groups = [];
        foreach ($entries as $dept) {
            foreach ($dept['products'] as $p) {
                $rate = $p['tax_rate'];
                $key = number_format($rate, 4);
                if (!isset($groups[$key])) {
                    $groups[$key] = ['rate' => $rate, 'subtotal' => 0];
                }
                $groups[$key]['subtotal'] += $p['amount'];
            }
        }
        $taxGroups = [];
        foreach ($groups as $group) {
            $rate = $group['rate'];
            $subtotal = $group['subtotal'];
            $tax = (int) floor($subtotal * $rate);
            $taxGroups[] = [
                'rate' => $rate,
                'subtotal_excl' => $subtotal,
                'tax' => $tax,
                'subtotal_incl' => $subtotal + $tax,
            ];
        }

        // 既存納品書チェック
        $note = \App\Models\DeliveryNote::where('delivery_customer_id', $customer->id)
            ->where('period_start', $request->period_start)
            ->where('period_end', $request->period_end)
            ->first();

        $defaultEmailBody = '';
        if ($customer->email) {
            $start = \Carbon\Carbon::parse($request->period_start)->format('n月j日');
            $end = \Carbon\Carbon::parse($request->period_end)->format('n月j日');
            $defaultEmailBody = "{$customer->name} 御中\n\nいつもお世話になっております。\nあさひ屋クリーニングです。\n\n{$start}〜{$end}分の納品書を添付いたします。\nご確認のほどよろしくお願いいたします。\n\nあさひ屋クリーニング";
        }

        return response()->json([
            'entries' => $entries,
            'tax_groups' => $taxGroups,
            'has_finalized' => $hasFinalized,
            'note_id' => $note?->id,
            'default_email_body' => $defaultEmailBody,
        ]);
    }
}
