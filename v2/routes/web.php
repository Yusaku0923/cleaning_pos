<?php

use App\Http\Controllers\Auth\DeviceLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManagerController;
use App\Http\Middleware\ResolvePosContext;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// 端末の自動ログイン (旧 /login/auto の代わり。URL にパスワードは載せない)
Route::get('/login/device/{token}', DeviceLoginController::class)
    ->middleware('throttle:10,1')
    ->name('login.device');

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// 選択中の担当者・顧客は URL (?m=&c=) で持つ。ResolvePosContext が request()->attributes に入れる。
Route::middleware(['auth', ResolvePosContext::class])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/menu', [HomeController::class, 'menu'])->name('menu');

    Route::prefix('manager')->group(function () {
        Route::get('update/{manager_id}', [ManagerController::class, 'update'])->whereNumber('manager_id')->name('manager.update');
    });

    Route::prefix('customer')->group(function () {
        Route::get('list', [CustomerController::class, 'list'])->name('customer.list');
        Route::get('search', [CustomerController::class, 'search'])->name('customer.search');
        Route::get('select/{id}', [CustomerController::class, 'select'])->whereNumber('id')->name('customer.select');
        Route::get('clear', [CustomerController::class, 'clear'])->name('customer.clear');
        Route::get('create', [CustomerController::class, 'create'])->name('customer.create');
        Route::post('store', [CustomerController::class, 'store'])->name('customer.store');
        Route::post('update/{id}', [CustomerController::class, 'update'])->whereNumber('id')->name('customer.update');
        Route::delete('delete/{id}', [CustomerController::class, 'destroy'])->whereNumber('id')->name('customer.delete');
    });

    // まだ移植していない画面。ホーム・メニューのリンク先の名前だけ先に置く (開くと 501)。
    // 各タスクで本物のルートに置き換えたら、ここから消す。
    foreach ([
        ['order/create', 'order.create'],                                 // Task 6
        ['order/all_show', 'order.all_show'],                             // Task 8
        ['order/show', 'order.show'],                                     // Task 8
        ['return', 'return.index'],                                       // Task 8
        ['payment', 'payment.index'],                                     // Task 8
        ['invoice', 'invoice.index'],                                     // Task 9
        ['invoice/payment_confimation', 'invoice.payment_confirmation'],  // Task 9 (URL は旧と同じ)
        ['daily_report', 'daily_report.index'],                           // Task 9
        ['clothes/create', 'clothes.create'],                             // Task 10
        ['receipt/edit', 'receipt.edit'],                                 // Task 10
        ['delivery', 'delivery.index'],                                   // Task 11
        ['delivery/sp/choice', 'delivery.sp.choice'],                     // Task 11
    ] as [$uri, $name]) {
        Route::get($uri, fn () => abort(501, '未実装'))->name($name);
    }
    Route::post('cache/clear', fn () => abort(501, '未実装'))->name('cache.clear'); // Task 10
});
