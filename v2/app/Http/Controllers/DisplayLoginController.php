<?php

namespace App\Http\Controllers;

use App\Services\CustomerDisplay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 顧客表示の端末。`display` ガードでログインする (POS の `web` ガードとは別なので、POS の画面・API は使えない)。
 */
class DisplayLoginController extends Controller
{
    public function login(Request $request, string $token): RedirectResponse
    {
        $store = CustomerDisplay::storeForToken($token);
        abort_if($store === null, 404);

        Auth::guard('display')->login($store);
        $request->session()->regenerate();

        return redirect('/customer_display');
    }

    public function show(): View
    {
        return view('customer_display');
    }
}
