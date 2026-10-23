<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** 納品書 (旧 delivery_notes)。店舗は取引先経由。 */
class DeliveryNote extends Model
{
    use BelongsToStore;

    protected $table = 'delivery_notes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public static function storeRelation(): ?string
    {
        return 'customer';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(DeliveryCustomer::class, 'delivery_customer_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DeliveryDailyEntry::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(DeliveryEmailLog::class);
    }
}
