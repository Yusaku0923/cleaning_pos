<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeliveryNote extends Model
{
    protected $fillable = ['delivery_customer_id', 'period_start', 'period_end', 'note_number'];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date'];

    public function customer()
    {
        return $this->belongsTo(DeliveryCustomer::class);
    }

    public function entries()
    {
        return $this->hasMany(DeliveryDailyEntry::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(DeliveryEmailLog::class);
    }

    /**
     * 顧客ごとの連番を採番してDeliveryNoteを作成（トランザクション内でFOR UPDATE）
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
