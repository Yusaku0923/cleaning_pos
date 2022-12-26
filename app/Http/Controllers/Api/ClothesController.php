<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use  Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Clothes;

class ClothesController extends Controller
{
    public function store(Request $request, Clothes $model) {
        $sort_key = Clothes::where('category', $request->category_id)->max('sort_key') + 1;
        $model->store_id = Auth::id();
        $model->category_id = $request->category_id;
        $model->sort_key = $sort_key;
        $model->name = $request->name;
        $model->name_kana = $request->name_kana;
        $model->price = $request->price;
        $model->tag_count = $request->tag_count;
        $model->save();

        return response()->json([
            'ok' => true
        ]);
    }

    public function update(Request $request, Clothes $model) {
        $sort_key = Clothes::where('id', $request->id)->value('sort_key');

        Clothes::find($request->id)->delete();

        $model->store_id = Auth::id();
        $model->category_id = $request->category_id;
        $model->sort_key = $sort_key;
        $model->name = $request->name;
        $model->name_kana = $request->name_kana;
        $model->price = $request->price;
        $model->tag_count = $request->tag_count;
        $model->save();

        return response()->json([
            'ok' => true
        ]);
    }

    public function delete(Request $request, Clothes $model) {
        Clothes::find($request->id)->delete();

        return response()->json([
            'ok' => true
        ]);
    }
}
