<?php

namespace App\Http\Controllers;

use App\Models\Category;

/** 旧 ClothesController。商品マスタ画面 (登録・更新・削除は Api\ClothesController / Api\CategoryController)。 */
class ClothesController extends Controller
{
    public function create()
    {
        $category_clothes = Category::with('clothes')->where('id', '!=', 1)->get()->toArray();

        return view('clothes.create')->with([
            'title' => '商　品　マ　ス　タ',
            'category_clothes' => $category_clothes,
        ]);
    }
}
