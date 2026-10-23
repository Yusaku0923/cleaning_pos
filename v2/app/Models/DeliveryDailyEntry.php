<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 日々の納品数 (旧 delivery_daily_entries)。店舗は品目経由。 */
class DeliveryDailyEntry extends Model
{
    use BelongsToStore;

    protected $table = 'delivery_daily_entries';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public static function storeRelation(): ?string
    {
        return 'product';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(DeliveryProduct::class, 'delivery_product_id');
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }
}
