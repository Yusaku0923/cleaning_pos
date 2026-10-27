<?php

use App\Http\Controllers\Api\ClothesController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerDisplayController;
use App\Http\Controllers\Api\CustomerInformationController;
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

    // 顧客表示へ送る (旧 POST /api/broadcast。ペイロードは旧と同じ)
    Route::post('customer-display', CustomerDisplayController::class)->name('api.customer_display');
});
