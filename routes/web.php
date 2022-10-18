<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\CustomerController;

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

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    // Route::post('/selected', [HomeController::class, 'selected'])->name('selected');

    Route::prefix('menu')->group(function () {
        
    });
    
    
    Route::prefix('manager')->group(function () {
        Route::post('update', [ManagerController::class, 'update'])->name('manager.update');
    });

    Route::prefix('customer')->group(function () {
        Route::get('search', [CustomerController::class, 'search'])->name('customer.search');
        Route::get('create', [CustomerController::class, 'create'])->name('customer.create');
        Route::post('store', [CustomerController::class, 'store'])->name('customer.store');
    });
});