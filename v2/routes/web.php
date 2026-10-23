<?php

use App\Http\Controllers\Auth\DeviceLoginController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// 端末の自動ログイン (旧 /login/auto の代わり。URL にパスワードは載せない)
Route::get('/login/device/{token}', DeviceLoginController::class)
    ->middleware('throttle:10,1')
    ->name('login.device');

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/', 'home')->name('home');
});
