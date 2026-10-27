<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Clothes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** 旧 Api\ClothesController。検索と、商品マスタの登録・更新・削除。 */
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

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $sortKey = Clothes::where('category_id', $data['category_id'])->max('sort_key') + 1;
        Clothes::create($data + ['sort_key' => $sortKey]);

        return $this->categoryResponse($data['category_id']);
    }

    /** 旧と同じく「削除して作り直す」(過去の伝票は古い行を指したまま。並び順は引き継ぐ)。 */
    public function update(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', 'integer']]);
        $data = $this->validated($request);

        DB::transaction(function () use ($request, $data) {
            $old = Clothes::findOrFail($request->input('id'));
            $sortKey = $old->sort_key;
            $old->delete();
            Clothes::create($data + ['sort_key' => $sortKey]);
        });

        return $this->categoryResponse($data['category_id']);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer'],
            'category_id' => ['required', 'integer'],
        ]);

        Clothes::findOrFail($request->input('id'))->delete();

        return $this->categoryResponse((int) $request->input('category_id'));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'name_kana' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'tag_count' => ['nullable', 'integer', 'min:0'],
        ]);
        // 自店舗のカテゴリだけ (StoreScope で絞られる。無ければ 404)
        Category::findOrFail($data['category_id']);

        return [
            'category_id' => (int) $data['category_id'],
            'name' => $data['name'],
            'name_kana' => mb_convert_kana($data['name_kana'] ?? '', 'rnk'),
            'price' => (int) $data['price'],
            'tag_count' => (int) ($data['tag_count'] ?? 1),
        ];
    }

    private function categoryResponse(int $categoryId): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'category' => Category::with('clothes')->where('id', $categoryId)->get()->toArray(),
        ]);
    }
}
