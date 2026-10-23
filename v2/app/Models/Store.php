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
        'remember_token',
    ];

    // POS (web ガード) はログインの保持を使わない。remember_token 列は顧客表示端末 (DisplayStore) 専用
    public function getRememberTokenName()
    {
        return '';
    }

    public function managers(): HasMany
    {
        return $this->hasMany(Manager::class);
    }
}
