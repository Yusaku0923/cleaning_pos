<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use  Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Clothes;
use App\Models\Category;

class ClothesController extends Controller
{
    public function store(Request $request, Clothes $model) {
        $sort_key = Clothes::where('category_id', $request->category_id)->max('sort_key') + 1;
        $model->store_id = Auth::id();
        $model->category_id = $request->category_id;
        $model->sort_key = $sort_key;
        $model->name = $request->name;
        $model->name_kana =  mb_convert_kana($request->name_kana, 'rnk');
        $model->price = $request->price;
        $model->tag_count = $request->tag_count;
        $model->save();

        return response()->json([
            'ok' => true,
            'category' => Category::with('clothes')->where('id', $request->category_id)->get()->toArray(),
        ]);
    }

    public function update(Request $request, Clothes $model) {
        $sort_key = Clothes::where('id', $request->id)->value('sort_key');

        Clothes::find($request->id)->delete();

        $model->store_id = Auth::id();
        $model->category_id = $request->category_id;
        $model->sort_key = $sort_key;
        $model->name = $request->name;
        $model->name_kana =  mb_convert_kana($request->name_kana, 'rnk');
        $model->price = $request->price;
        $model->tag_count = $request->tag_count;
        $model->save();

        return response()->json([
            'ok' => true,
            'category' => Category::with('clothes')->where('id', $request->category_id)->get()->toArray(),
        ]);
    }

    public function delete(Request $request) {
        Clothes::find($request->id)->delete();

        return response()->json([
            'ok' => true,
            'category' => Category::with('clothes')->where('id', $request->category_id)->get()->toArray(),
        ]);
    }
}
