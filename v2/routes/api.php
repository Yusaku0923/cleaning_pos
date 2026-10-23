<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API は Sanctum の SPA 方式 (同一オリジンのセッション Cookie)。画面ごとのトークン発行はしない。
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
});
