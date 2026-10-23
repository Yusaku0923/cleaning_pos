<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 納品の品目 (旧 delivery_products)。店舗は部署 → 取引先経由。 */
class DeliveryProduct extends Model
{
    use BelongsToStore;

    protected $table = 'delivery_products';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'float',
        ];
    }

    public static function storeRelation(): ?string
    {
        return 'department';
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(DeliveryDepartment::class, 'delivery_department_id');
    }

    public function dailyEntries(): HasMany
    {
        return $this->hasMany(DeliveryDailyEntry::class);
    }
}
