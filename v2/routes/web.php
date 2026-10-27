<?php

use App\Http\Controllers\Auth\DeviceLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\ClothesController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DisplayLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TagCounterController;
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

// 顧客表示の端末 (旧 /customer_display は公開だった。v2 は表示端末のログインが要る)
Route::get('/display/login/{token}', [DisplayLoginController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('display.login');
// 未ログインでも POS の /login には飛ばさず、スタッフ向けの案内を出す (コントローラで判定)
Route::get('/customer_display', [DisplayLoginController::class, 'show'])->name('customer_display');
// 表示画面のキープアライブ (10 分ごと)。セッションを新しく保ち、切れていれば remember cookie で入り直す
Route::get('/display/ping', [DisplayLoginController::class, 'ping'])->name('display.ping');

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

    Route::prefix('tag')->group(function () {
        Route::post('update', [TagCounterController::class, 'update'])->name('tag.update');
    });

    Route::get('payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::get('return', [ReturnController::class, 'index'])->name('return.index');

    Route::prefix('order')->group(function () {
        Route::get('create', [OrderController::class, 'create'])->name('order.create');
        Route::get('all_show', [OrderHistoryController::class, 'allShow'])->name('order.all_show');
        Route::get('show', [OrderHistoryController::class, 'show'])->name('order.show');
        // 直前預り取り消し (旧と同じ DELETE。GET では消さない)
        Route::delete('delete/{order_id}', [OrderHistoryController::class, 'destroy'])->name('order.delete');
    });

    // 請求書発行・入金確認・請求書 PDF (旧と同じ URL。PDF は GET では表示だけ、発行の記録は POST /invoice/issue)
    Route::prefix('invoice')->group(function () {
        Route::get('', [InvoiceController::class, 'index'])->name('invoice.index');
        Route::get('payment_confimation', [InvoiceController::class, 'paymentConfirmation'])->name('invoice.payment_confirmation');
        Route::get('pdf', [InvoiceController::class, 'pdf'])->name('invoice.pdf');
        Route::post('issue', [InvoiceController::class, 'issue'])->name('invoice.issue');
    });

    // 日報と日報 PDF (旧 daily_report/generate/{date} → daily-report/{date}/pdf)
    Route::get('daily_report', [DailyReportController::class, 'index'])->name('daily_report.index');
    Route::get('daily-report/{date}/pdf', [DailyReportController::class, 'pdf'])->name('daily_report.pdf');

    // まだ移植していない画面。ホーム・メニューのリンク先の名前だけ先に置く (開くと 501)。
    // 各タスクで本物のルートに置き換えたら、ここから消す。
    foreach ([
        ['delivery', 'delivery.index'],                                   // Task 11
        ['delivery/sp/choice', 'delivery.sp.choice'],                     // Task 11
    ] as [$uri, $name]) {
        Route::get($uri, fn () => abort(501, '未実装'))->name($name);
    }

    // 商品マスタ・レシート (プリンタ IP) 設定・キャッシュクリア
    Route::get('clothes/create', [ClothesController::class, 'create'])->name('clothes.create');
    Route::get('receipt/edit', [SettingsController::class, 'editPrinter'])->name('receipt.edit');
    Route::post('settings/printer', [SettingsController::class, 'updatePrinter'])->name('settings.printer');
    Route::post('cache/clear', [CacheController::class, 'clear'])->name('cache.clear');
});
