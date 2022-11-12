<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\ReturnController;

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
    Route::post('order', [OrdersController::class, 'store'])->name('order.store');
    Route::get('receipt/{order_id}', [OrdersController::class, 'fetchReceiptInfo'])->name('order.receipt');

    Route::post('return/update', [ReturnController::class, 'update'])->name('order.update');
});
