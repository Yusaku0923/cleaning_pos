<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** 顧客の CS 情報 (旧 customer_information)。店舗は顧客経由。 */
class CustomerInformation extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'customer_information';

    protected $guarded = ['id'];

    public static function storeRelation(): ?string
    {
        return 'customer';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** 顧客情報欄に出す CS 情報 (旧はセッション customer_info。古い順) */
    public static function forScreen(Customer $customer): array
    {
        return static::where('customer_id', $customer->id)->orderBy('created_at', 'asc')->get()->toArray();
    }
}
