<?php

use App\Http\Controllers\Api\ClothesController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerInformationController;
use App\Http\Controllers\Api\OrderController;
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
    // レシート印刷用データ (旧と同じ URL・キー名)
    Route::get('receipt/{order}', [OrderController::class, 'receipt'])->name('api.receipt');
});
