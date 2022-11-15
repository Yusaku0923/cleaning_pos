<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Clothes extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function categories()
    {
        return $this->belongsTo(Category::class);
    }

    public function order_clothes(){
        return $this->hasMany(OrderClothes::class, 'clothes_id');
    }

    public function fetchOftenOrdered($customer_id, $limit = 12)
    {
        $clothes = OrderClothes::selectRaw(DB::raw('clothes.*, COUNT(order_clothes.clothes_id) AS count'))
                                ->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id')
                                ->join('orders', 'order_clothes.order_id', '=', 'orders.id')
                                ->where('orders.customer_id', $customer_id)
                                ->where('order_clothes.clothes_id', '<>', 999)
                                ->groupBy('order_clothes.clothes_id')
                                ->orderByDesc('count')
                                ->limit($limit)
                                ->get()->toArray();

        return $clothes;
    }
}
