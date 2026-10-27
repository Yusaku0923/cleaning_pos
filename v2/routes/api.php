<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ClientErrorController;
use App\Http\Controllers\Api\ClothesController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerDisplayController;
use App\Http\Controllers\Api\CustomerInformationController;
use App\Http\Controllers\Api\DeliveryEntryController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReturnController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API は Sanctum の SPA 方式 (同一オリジンのセッション Cookie)。画面ごとのトークン発行はしない。
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::post('customer/search', [CustomerController::class, 'search'])->name('api.customer.search');
    Route::post('customer_info/store', [CustomerInformationController::class, 'store'])->name('api.customer_info.store');
    Route::post('customer_info/delete', [CustomerInformationController::class, 'delete'])->name('api.customer_info.delete');

    Route::post('clothes/search', [ClothesController::class, 'search'])->name('api.clothes.search');
    // 商品マスタ (旧と同じ URL・キー名)
    Route::post('clothes/store', [ClothesController::class, 'store'])->name('api.clothes.store');
    Route::post('clothes/update', [ClothesController::class, 'update'])->name('api.clothes.update');
    Route::post('clothes/delete', [ClothesController::class, 'delete'])->name('api.clothes.delete');
    Route::post('category/store', [CategoryController::class, 'store'])->name('api.category.store');
    Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
    // 預り一覧・全体検索・取り消し・タグ変更・入金・お渡し (旧と同じ URL・キー名)
    Route::post('order/search', [OrderController::class, 'search'])->name('api.order.search');
    Route::post('order/delete', [OrderController::class, 'delete'])->name('api.order.delete');
    Route::post('order/update_tag', [OrderController::class, 'updateTag'])->name('api.order.update_tag');
    Route::post('payment', [OrderController::class, 'payment'])->name('api.order.payment');
    Route::post('return/update', [ReturnController::class, 'update'])->name('api.return.update');
    // レシート印刷用データ (旧と同じ URL・キー名)
    Route::get('receipt/{order}', [OrderController::class, 'receipt'])->name('api.receipt');

    // 請求書発行画面の検索・繰越・締日整列 (旧と同じ URL・キー名)、入金確認画面の「入金確認」
    Route::post('invoice/search', [InvoiceController::class, 'search'])->name('api.invoice.search');
    Route::post('invoice/carry_over', [InvoiceController::class, 'carryOver'])->name('api.invoice.carry_over');
    Route::post('invoice/align_cutoff_date', [InvoiceController::class, 'alignCutoffDate'])->name('api.invoice.align_cutoff_date');
    Route::post('invoice/payment_confirmation', [InvoiceController::class, 'paymentConfirmation'])->name('api.invoice.payment_confirmation');

    // 納品書の日次入力・集計プレビュー (旧と同じ URL・キー名)
    Route::get('delivery/customers', [DeliveryEntryController::class, 'customers'])->name('delivery.customers');
    Route::get('delivery/customers/{id}/departments', [DeliveryEntryController::class, 'departments'])->whereNumber('id')->name('delivery.departments');
    Route::get('delivery/entries', [DeliveryEntryController::class, 'index'])->name('delivery.entries.index');
    Route::post('delivery/entries', [DeliveryEntryController::class, 'store'])->name('delivery.entries.store');
    Route::delete('delivery/entries', [DeliveryEntryController::class, 'destroy'])->name('delivery.entries.destroy');
    Route::get('delivery/entry-status', [DeliveryEntryController::class, 'entryStatus'])->name('delivery.entry_status');
    Route::get('delivery/preview', [DeliveryEntryController::class, 'preview'])->name('delivery.preview');

    // 顧客表示へ送る (旧 POST /api/broadcast。ペイロードは旧と同じ)
    Route::post('customer-display', CustomerDisplayController::class)->name('api.customer_display');
});

// ブラウザの JS エラー収集 (旧と同じく未ログインでも送れる。1 IP あたり 60 回/分)
Route::post('client-error', [ClientErrorController::class, 'store'])
    ->middleware('throttle:client-error')
    ->name('api.client_error.store');
