<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function store(Request $request, Category $model) {
        $latest = Category::where('id', '<', 999)->max('id');
        $model->id = $latest + 1;
        $model->store_id = Auth::id();
        $model->name = $request->name;
        $model->save();

        return response()->json([
            'ok' => true,
        ]);
    }
}
