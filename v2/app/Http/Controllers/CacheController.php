<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

/**
 * キャッシュクリアだけ。旧の「Vue 再ビルド」(サーバーで npm を実行) は作らない。
 * シェルは呼ばない (Artisan::call のみ)。
 */
class CacheController extends Controller
{
    public function clear()
    {
        Artisan::call('optimize:clear');

        return back();
    }
}
