<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 納品書の取引先 (旧 delivery_customers)。旧は店舗を持たないため v2 で store_id を追加。 */
class DeliveryCustomer extends Model
{
    use BelongsToStore;

    protected $table = 'delivery_customers';

    protected $guarded = ['id'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(DeliveryDepartment::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }
}
