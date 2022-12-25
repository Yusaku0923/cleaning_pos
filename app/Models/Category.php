<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;


class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function clothes()
    {
        return $this->hasMany(Clothes::class)->orderBy('sort_key', 'asc');
    }

    public function getAllWithClothes()
    {
        $categories = Category::get();
        dd($categories);

        return $categories;
    }
}
