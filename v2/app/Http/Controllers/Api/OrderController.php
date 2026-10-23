<?php

namespace App\Http\Controllers\Api;

use App\Domain\Order\OrderService;
use App\Domain\Receipt\ReceiptData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/** 旧 Api\OrdersController の store / fetchReceiptInfo。 */
class OrderController extends Controller
{
    /** POST /api/orders → 201 {order_id, tags, total}。total はサーバーで計算した合計。 */
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->store($request->toData());

        return response()->json([
            'order_id' => $order->id,
            'tags' => $order->items->pluck('tag')->reject(fn ($t) => $t === OrderService::NO_TAG)->values()->all(),
            'total' => (int) $order->amount,
        ], 201);
    }

    /** GET /api/receipt/{order} → レシート印刷用データ (旧と同じキー)。他店舗の注文は 404。 */
    public function receipt(string $order): JsonResponse
    {
        if (! ctype_digit($order)) {
            abort(404);
        }
        $model = Order::find((int) $order);
        if ($model === null) {
            return response()->json(['error' => '注文が見つかりません'], 404);
        }

        $receipt = ReceiptData::forOrder($model);
        if ($receipt === null) {
            return response()->json(['error' => '顧客が見つかりません'], 422);
        }

        return response()->json($receipt);
    }
}
