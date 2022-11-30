<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ClothesController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\TagNumberController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes([
    'register' => false,
    'confirm' => false,
    'email' => false,
    'reset' => false,
]);

Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/menu', [HomeController::class, 'menu'])->name('menu');

    Route::prefix('tag')->group(function () {
        Route::post('update', [TagNumberController::class, 'update'])->name('tag.update');
    });

    Route::prefix('payment')->group(function () {
        Route::get('', [PaymentController::class, 'index'])->name('payment.index');
    });

    Route::prefix('return')->group(function () {
        Route::get('', [ReturnController::class, 'index'])->name('return.index');
        Route::post('update', [ReturnController::class, 'update'])->name('return.update');
    });

    Route::prefix('daily_report')->group(function () {
        Route::get('', [DailyReportController::class, 'index'])->name('daily_report.index');
        Route::get('generate/{date}', [DailyReportController::class, 'generate'])->name('daily_report.generate');
    });

    Route::prefix('invoice')->group(function () {
        Route::get('', [InvoiceController::class, 'index'])->name('invoice.index');
        Route::get('generate', [InvoiceController::class, 'generate'])->name('invoice.generate');
    });

    Route::prefix('manager')->group(function () {
        Route::post('update', [ManagerController::class, 'update'])->name('manager.update');
    });

    Route::prefix('order')->group(function () {
        Route::get('create', [OrdersController::class, 'create'])->name('order.create');
        Route::get('show', [OrdersController::class, 'show'])->name('order.show');
        Route::delete('delete/{order_id}', [OrdersController::class, 'destroy'])->name('order.delete');
    });

    Route::prefix('customer')->group(function () {
        Route::get('search', [CustomerController::class, 'search'])->name('customer.search');
        Route::get('select/{id}', [CustomerController::class, 'select'])->name('customer.select');
        Route::get('clear', [CustomerController::class, 'clear'])->name('customer.clear');
        Route::get('create', [CustomerController::class, 'create'])->name('customer.create');
        Route::post('store', [CustomerController::class, 'store'])->name('customer.store');
        Route::post('update/{id}', [CustomerController::class, 'update'])->name('customer.update');
    });

    Route::prefix('clothes')->group(function () {
        Route::get('create', [ClothesController::class, 'create'])->name('clothes.create');
    });
});