<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 店の端末の自動ログイン。URL に載るのは端末トークンだけ (パスワードは載せない)。
 */
class DeviceLoginController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $device = DeviceToken::findActive($token);
        abort_if($device === null, 404);

        Auth::guard('web')->login($device->store);
        $request->session()->regenerate();
        $device->forceFill(['last_used_at' => now()])->save();

        return redirect('/');
    }
}
