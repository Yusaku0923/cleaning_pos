<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\CustomerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::post('customer/search', [CustomerController::class, 'search'])->name('order.search');
    Route::post('order/store', [OrdersController::class, 'store'])->name('order.store');
    Route::post('order/search', [OrdersController::class, 'search'])->name('order.search');
    Route::post('payment', [OrdersController::class, 'payment'])->name('order.payment');
    Route::get('receipt/{order_id}', [OrdersController::class, 'fetchReceiptInfo'])->name('order.receipt');

    Route::post('return/update', [ReturnController::class, 'update'])->name('order.update');
});
