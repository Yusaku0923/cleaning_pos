<?php

namespace App\Models\Concerns;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Model;

/**
 * 店舗に属するモデル (spec §6-10)。ログイン中は自店舗の行だけを返すグローバルスコープを付ける。
 * ID を直接指定しても (find / findOrFail / ルートモデル結合) 他店舗の行は取れない。
 *
 * - テーブルが store_id を持つモデル: storeRelation() は null のまま。作成時に store_id を自動で入れる。
 * - 持たないモデル: storeRelation() で親のリレーション名を返す。親のスコープ (whereHas) で絞る。
 *   親が論理削除されていても子は隠さない (旧と同じ見え方)。
 * - CLI (artisan, legacy:import / legacy:verify) と未ログインでは絞らない。
 */
trait BelongsToStore
{
    public static function bootBelongsToStore(): void
    {
        static::addGlobalScope(new StoreScope);

        static::creating(function (Model $model) {
            if (static::storeRelation() !== null || $model->getAttribute('store_id') !== null) {
                return;
            }
            $storeId = StoreScope::currentStoreId();
            if ($storeId !== null) {
                $model->setAttribute('store_id', $storeId);
            }
        });
    }

    /** null = このテーブルが store_id を持つ。文字列 = 店舗をたどる親リレーション名。 */
    public static function storeRelation(): ?string
    {
        return null;
    }
}
