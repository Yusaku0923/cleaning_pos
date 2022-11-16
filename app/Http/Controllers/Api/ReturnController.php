<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\OrderClothes;

class ReturnController extends Controller
{
    public function update(Request $request) {
        OrderClothes::whereIn('id', $request->items)
                    ->update(['handed_at'=> date('Y-m-d H:i:s')]);
        $order_id = OrderClothes::where('id', $request->items[0])->value('order_id');
        $is_handed_all = OrderClothes::where('order_id', $order_id)
                                    ->whereNull('handed_at')
                                    ->exists();
        if (!$is_handed_all) {
            Order::find($order_id)->update([
                'handed_at' => date('Y-m-d H:i:s')
            ]);
        }

        return response()->json([
            'ok' => true
        ]);
    }
}
