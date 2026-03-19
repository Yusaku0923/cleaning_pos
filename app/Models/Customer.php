<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'manager_id',
        'name',
        'name_kana',
        'phone_number',
        'birth_day',
        'sex',
        'point',
        'cutoff_date',
        'is_invoice',
        'needs_payment_confimation',
        'needs_return_confimation',
        'total_sales',
        'number_of_visits',
        'created_at',
        'updated_at',
    ];

    // JSON出力に正しいスペルのキーを含める
    protected $appends = ['needs_payment_confirmation'];

    // アクセサ: $customer->needs_payment_confirmation で取得
    public function getNeedsPaymentConfirmationAttribute()
    {
        return $this->attributes['needs_payment_confimation'] ?? false;
    }

    // ミューテータ: $customer->needs_payment_confirmation = ... でセット
    public function setNeedsPaymentConfirmationAttribute($value)
    {
        $this->attributes['needs_payment_confimation'] = $value;
    }
}
