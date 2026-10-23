<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * 店舗 = ログインアカウント (guard: web)。
 */
class Store extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    // stores に remember_token は無い (ログインの保持は使わない)
    public function getRememberTokenName()
    {
        return '';
    }
}
