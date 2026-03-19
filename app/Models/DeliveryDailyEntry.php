<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDailyEntry extends Model
{
    protected $fillable = ['delivery_product_id', 'delivery_note_id', 'date', 'quantity', 'note'];

    protected $casts = ['date' => 'date'];

    public function product()
    {
        return $this->belongsTo(DeliveryProduct::class, 'delivery_product_id');
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class);
    }
}
