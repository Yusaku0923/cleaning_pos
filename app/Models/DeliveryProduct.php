<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryProduct extends Model
{
    protected $fillable = ['delivery_department_id', 'name', 'unit_price', 'tax_rate', 'sort_order'];

    protected $casts = ['tax_rate' => 'float'];

    public function department()
    {
        return $this->belongsTo(DeliveryDepartment::class);
    }

    public function dailyEntries()
    {
        return $this->hasMany(DeliveryDailyEntry::class);
    }
}
