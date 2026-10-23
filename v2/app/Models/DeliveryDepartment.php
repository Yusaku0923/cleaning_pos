<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 納品先の部署 (旧 delivery_departments)。店舗は取引先経由。 */
class DeliveryDepartment extends Model
{
    use BelongsToStore;

    protected $table = 'delivery_departments';

    protected $guarded = ['id'];

    public static function storeRelation(): ?string
    {
        return 'customer';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(DeliveryCustomer::class, 'delivery_customer_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(DeliveryProduct::class);
    }
}
