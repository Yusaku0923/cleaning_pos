<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ClothesController;
use App\Http\Controllers\DailyReportController;

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

    Route::prefix('daily_report')->group(function () {
        Route::get('', [DailyReportController::class, 'index'])->name('daily_report.index');
    });

    Route::prefix('manager')->group(function () {
        Route::post('update', [ManagerController::class, 'update'])->name('manager.update');
    });

    Route::prefix('order')->group(function () {
        Route::get('create', [OrdersController::class, 'create'])->name('order.create');
    });

    Route::prefix('customer')->group(function () {
        Route::get('search', [CustomerController::class, 'search'])->name('customer.search');
        Route::get('select/{id}', [CustomerController::class, 'select'])->name('customer.select');
        Route::get('clear', [CustomerController::class, 'clear'])->name('customer.clear');
        Route::get('create', [CustomerController::class, 'create'])->name('customer.create');
        Route::post('store', [CustomerController::class, 'store'])->name('customer.store');
    });

    Route::prefix('clothes')->group(function () {
        Route::get('create', [ClothesController::class, 'create'])->name('clothes.create');
    });
});