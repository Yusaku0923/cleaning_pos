<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * 店舗 = ログインアカウント (guard: web)。旧 stores と同じカラム。
 * 認証に使うので店舗で絞るスコープは付けない。
 */
class Store extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'postal_code',
        'address',
        'phone_number',
    ];

    protected $hidden = [
        'password',
    ];

    // stores に remember_token は無い (ログインの保持は使わない)
    public function getRememberTokenName()
    {
        return '';
    }

    public function managers(): HasMany
    {
        return $this->hasMany(Manager::class);
    }
}
