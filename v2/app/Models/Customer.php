<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** 顧客 (旧 customers)。店舗は担当者経由。 */
class Customer extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'customers';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'birth_day' => 'date',
            'is_invoice' => 'boolean',
            'needs_payment_confirmation' => 'boolean',
            'needs_return_confirmation' => 'boolean',
        ];
    }

    public static function storeRelation(): ?string
    {
        return 'manager';
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    public function information(): HasMany
    {
        return $this->hasMany(CustomerInformation::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
