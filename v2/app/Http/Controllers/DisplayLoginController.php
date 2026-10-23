<?php

namespace App\Http\Controllers;

use App\Services\CustomerDisplay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * 顧客表示の端末。`display` ガードでログインする (POS の `web` ガードとは別なので、POS の画面・API は使えない)。
 * ログインは remember cookie で保持し、営業中に放置してセッションが切れても表示を続ける。
 */
class DisplayLoginController extends Controller
{
    public function login(Request $request, string $token): RedirectResponse
    {
        $store = CustomerDisplay::storeForToken($token);
        abort_if($store === null, 404);

        Auth::guard('display')->login($store, remember: true);
        $request->session()->regenerate();

        return redirect('/customer_display');
    }

    /** 未ログインでも POS の /login には飛ばさない (お客様にログイン画面を見せない) */
    public function show(): Response
    {
        if (! Auth::guard('display')->check()) {
            return response()->view('display_login_required', [], 401);
        }

        return response()->view('customer_display');
    }

    public function ping(): Response|JsonResponse
    {
        if (! Auth::guard('display')->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->noContent();
    }
}
