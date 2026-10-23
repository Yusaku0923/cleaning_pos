<?php

namespace App\Models\Scopes;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

/** ログイン中の店舗で絞る。使い方は App\Models\Concerns\BelongsToStore。 */
class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $storeId = self::currentStoreId();
        if ($storeId === null) {
            return;
        }

        $relation = $model::storeRelation();
        if ($relation === null) {
            $builder->where($model->qualifyColumn('store_id'), $storeId);

            return;
        }

        // 親のモデルにもこのスコープが付いているので、whereHas で親の店舗に絞られる
        $builder->whereHas($relation, fn (Builder $parent) => $parent->withoutGlobalScope(SoftDeletingScope::class));
    }

    /** ログイン中 (web / sanctum) の店舗 ID。CLI と未ログインでは null (= 絞らない)。 */
    public static function currentStoreId(): ?int
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        $user = Auth::user();

        return $user instanceof Store ? (int) $user->getKey() : null;
    }
}
