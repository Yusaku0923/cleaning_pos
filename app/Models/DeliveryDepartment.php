<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDepartment extends Model
{
    protected $fillable = ['delivery_customer_id', 'name', 'sort_order'];

    public function customer()
    {
        return $this->belongsTo(DeliveryCustomer::class);
    }

    public function products()
    {
        return $this->hasMany(DeliveryProduct::class)->orderBy('sort_order');
    }
}
