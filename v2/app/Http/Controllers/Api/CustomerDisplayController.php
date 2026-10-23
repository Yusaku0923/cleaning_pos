<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 旧 POST /api/broadcast。画面 (OrderComponent 等) から顧客表示へ送る。
 * 送り先はログイン中の店舗のチャンネルだけ (店舗 ID は入力から取らない)。
 */
class CustomerDisplayController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['event' => ['required', 'string', 'max:20']]);

        // 旧は Auth::user()->is_invoice (店舗にこの列は無く常に false) を見ていたので、常に送る
        CustomerDisplay::push((int) $request->user()->id, $request->all());

        return response()->json([]);
    }
}
