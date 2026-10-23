<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 納品書メールの送信記録 (旧 delivery_email_logs)。店舗は取引先経由。 */
class DeliveryEmailLog extends Model
{
    use BelongsToStore;

    protected $table = 'delivery_email_logs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public static function storeRelation(): ?string
    {
        return 'customer';
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class, 'delivery_note_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(DeliveryCustomer::class, 'delivery_customer_id');
    }
}
