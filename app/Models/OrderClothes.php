<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OrderClothes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_clothes';
    protected $guarded = [];
    
    public function clothes()
    {
        return $this->belongsTo(Clothes::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}
