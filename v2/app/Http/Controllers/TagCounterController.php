<?php

namespace App\Http\Controllers;

use App\Models\Manager;
use App\Models\TagCounter;
use Illuminate\Http\Request;

/** 旧 TagNumberController::update (ホームの「タグ番号更新」)。担当者は URL (?m=) から。 */
class TagCounterController extends Controller
{
    public function update(Request $request)
    {
        /** @var Manager|null $manager */
        $manager = $request->attributes->get('manager');
        if ($manager !== null) {
            // 入力欄と同じ範囲 (1〜10 / 0〜999) = タグ 1-000〜10-999。旧は確認がなく、空欄だと 0 が入った
            $v = validator($request->all(), [
                'tag_head' => ['required', 'integer', 'between:1,10'],
                'tag_body' => ['required', 'integer', 'between:0,999'],
            ]);
            if ($v->passes()) {
                // 旧と同じ値 (tag_head * 1000 + tag_body)。採番 (TagAllocator) と同じく論理削除された行も対象
                TagCounter::withTrashed()->where('manager_id', $manager->id)->update([
                    'value' => (int) $request->input('tag_head') * 1000 + (int) $request->input('tag_body'),
                ]);
            }
        }

        return redirect(pos_url('home'));
    }
}
