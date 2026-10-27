<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        // 旧と同じく 999 未満の最大 ID + 1 (999 は「〃」用に空けてある)。他店舗・削除済みの分も含めて採番する
        $next = Category::withoutGlobalScopes()->withTrashed()->where('id', '<', 999)->max('id') + 1;
        $category = new Category(['name' => $validated['name']]);
        $category->id = $next;
        $category->save();

        return response()->json(['ok' => true]);
    }
}
