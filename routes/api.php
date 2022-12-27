<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrdersController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ClothesController;
use App\Http\Controllers\Api\CustomerInformationController;

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
    Route::post('clothes/store', [ClothesController::class, 'store'])->name('clothes.store');
    Route::post('clothes/update', [ClothesController::class, 'update'])->name('clothes.update');
    Route::post('clothes/delete', [ClothesController::class, 'delete'])->name('clothes.delete');

    Route::post('customer/search', [CustomerController::class, 'search'])->name('customer.search');

    Route::post('order/store', [OrdersController::class, 'store'])->name('order.store');
    Route::post('order/search', [OrdersController::class, 'search'])->name('order.search');
    Route::post('order/delete', [OrdersController::class, 'delete'])->name('order.delete');
    Route::post('payment', [OrdersController::class, 'payment'])->name('order.payment');
    Route::get('receipt/{order_id}', [OrdersController::class, 'fetchReceiptInfo'])->name('order.receipt');

    Route::post('customer_info/store', [CustomerInformationController::class, 'store'])->name('customer_info.store');
    Route::post('customer_info/delete', [CustomerInformationController::class, 'delete'])->name('customer_info.delete');

    Route::post('invoice/search', [InvoiceController::class, 'search'])->name('invoice.search');

    Route::post('return/update', [ReturnController::class, 'update'])->name('return.update');
});
