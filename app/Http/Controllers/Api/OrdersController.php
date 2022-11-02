<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderClothes;
use App\Models\TagNumber;

class OrdersController extends Controller
{
    public function store(Request $request) {
        $order = Order::query()->create([
            'store_id' => Auth::id(),
            'customer_id' => $request->customer_id,
            'amount' => $request->amount,
            'discount' => $request->discount,
            'payment' => $request->payment,
            'is_registered_as_invoice' => $request->invoice,
            'has_paid' => true,
        ]);

        $tag = TagNumber::find($request->manager_id)->value('tag_number');
        $response = [];
        foreach ($request->order as $clothes_id => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $converted_tag = $this->convertTagFormat($tag);
                OrderClothes::query()->create([
                    'order_id' => $order->id,
                    'clothes_id' => $clothes_id,
                    'tag' => $converted_tag
                ]);

                    if (!isset($response[$clothes_id])) {
                        $response[$clothes_id] = $converted_tag;
                    } else if ($i >= 2 && $i === $count) {
                        $response[$clothes_id] .= '～' . $converted_tag;
                    }

                if ($tag >= 9999) {
                    $tag = 0;
                } else {
                    $tag++;
                }
            }
        }
        TagNumber::where('manager_id', $request->manager_id)
                ->update([
                    'tag_number' => $tag
                ]);

        return response()->json([
            'order_id' => $order->id
        ]);
    }

    private function convertTagFormat($tag) {
        $formated_tag = '';

        if ($tag < 10) {
            $formated_tag = '0-00' . (string)$tag;
        } else if ($tag < 100) {
            $formated_tag = '0-0' . (string)$tag;
        } else if ($tag < 1000) {
            $formated_tag = '0-' . (string)$tag;
        } else {
            $formated_tag = substr((string)$tag, 0, 1) . '-' . (string)($tag % 1000);
        }

        return $formated_tag;
    }
}
