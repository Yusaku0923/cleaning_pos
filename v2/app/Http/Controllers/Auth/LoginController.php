<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $key = $this->throttleKey($request);
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            abort(429);
        }

        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(self::redirectPath($request));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    // 旧 LoginController::redirectTo と同じ: スマホは納品書入力へ
    public static function redirectPath(Request $request): string
    {
        $ua = (string) $request->header('User-Agent', '');

        return preg_match('/iPhone|Android.*Mobile/i', $ua) ? '/delivery/sp/choice' : '/';
    }

    // email + IP 単位
    private function throttleKey(Request $request): string
    {
        return 'login:'.Str::lower((string) $request->input('email')).'|'.$request->ip();
    }
}
