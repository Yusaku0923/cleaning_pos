<?php

namespace App\Http\Controllers\Api;

use App\Domain\Order\OrderService;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** 旧 Api\ReturnController (お渡し画面の「お渡し」)。 */
class ReturnController extends Controller
{
    /**
     * POST /api/return/update {items: [品物ID], handed_at: 'Y-m-d'|null} → {ok: true}
     * 品物は 1 つの注文のものだけ (画面は 1 伝票ずつ渡す)。他店舗の品物が混ざれば 404、複数の注文なら 422。
     */
    public function update(Request $request, OrderService $orders): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
            'handed_at' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $ids = array_values(array_unique(array_map('intval', $data['items'])));

        $items = OrderItem::whereIn('id', $ids)->get(['id', 'order_id']);
        abort_if($items->count() !== count($ids), 404);
        $orderIds = $items->pluck('order_id')->unique();
        if ($orderIds->count() !== 1) {
            return response()->json(['message' => '品物は 1 つの伝票から選んでください。'], 422);
        }

        $orders->handItems(
            Order::findOrFail($orderIds->first()),
            $ids,
            empty($data['handed_at']) ? null : CarbonImmutable::parse($data['handed_at']),
        );

        return response()->json(['ok' => true]);
    }
}
