<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    /**
     * 取引先ごとの連番を採番して納品書を作る (旧と同じ。トランザクション内で FOR UPDATE)。
     * 同時に採番しても (取引先, 番号) の一意制約で片方が失敗するので、番号は重ならない。
     */
    public static function createWithNumber(int $customerId, string $periodStart, string $periodEnd): self
    {
        return DB::transaction(function () use ($customerId, $periodStart, $periodEnd) {
            $max = self::where('delivery_customer_id', $customerId)
                ->lockForUpdate()
                ->max('note_number') ?? 0;

            return self::create([
                'delivery_customer_id' => $customerId,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'note_number' => $max + 1,
            ]);
        });
    }
}
