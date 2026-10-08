<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    protected function redirectTo()
    {
        $ua = request()->header('User-Agent', '');
        $isMobile = (bool) preg_match('/iPhone|Android.*Mobile/i', $ua);
        return $isMobile ? '/delivery/sp/choice' : '/';
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function autoLogin(Request $request)
    {
        $credentials = [
            'email' => $request->query('id'),
            'password' => $request->query('pass'),
        ];

        // 資格情報がURLに載るため、リンク先へ Referer として漏れないようにする。
        // (既存端末のブックマーク互換のため仕組み自体は維持。総当たり対策は throttle で行う)
        $noReferrer = ['Referrer-Policy' => 'no-referrer'];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect($this->redirectTo())->withHeaders($noReferrer);
        }

        return redirect()->route('login')->withHeaders($noReferrer);
    }
}
