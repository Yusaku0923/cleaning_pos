<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryEmailLog extends Model
{
    protected $fillable = [
        'delivery_note_id', 'delivery_customer_id',
        'period_start', 'period_end', 'sent_to', 'sent_at', 'status'
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function note()
    {
        return $this->belongsTo(DeliveryNote::class);
    }
}
