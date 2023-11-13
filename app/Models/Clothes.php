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


    public function fetchOftenOrdered($customer_id)
    {
        $clothes = OrderClothes::selectRaw(DB::raw('clothes.*, COUNT(order_clothes.clothes_id) AS count'))
                                ->join('clothes', 'order_clothes.clothes_id', '=', 'clothes.id')
                                ->join('orders', 'order_clothes.order_id', '=', 'orders.id')
                                ->where('orders.customer_id', $customer_id)
                                ->where('order_clothes.clothes_id', '<>', 999)
                                ->whereNull('clothes.deleted_at')
                                ->groupBy('order_clothes.clothes_id')
                                ->orderByDesc('count')
                                ->get()->toArray();

        return $clothes;
    }

    public function searchItemName($name)
    {
        $clothes = Clothes::where("name", "LIKE", '%'.$name.'%')
                            ->get()->toArray();

        return $clothes;
    }
}
