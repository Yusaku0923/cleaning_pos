<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clothes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** 旧 Api\ClothesController。注文画面の「検索」で使う search だけ (登録・更新・削除は Task 10)。 */
class ClothesController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate(['name' => ['nullable', 'string', 'max:255']]);
        $name = addcslashes((string) $request->input('name'), '\%_');

        return response()->json([
            'ok' => true,
            'clothes' => Clothes::where('name', 'LIKE', '%'.$name.'%')->get()->toArray(),
        ]);
    }
}
