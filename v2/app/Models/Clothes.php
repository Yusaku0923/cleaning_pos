<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** 商品 (旧 clothes)。 */
class Clothes extends Model
{
    use BelongsToStore, SoftDeletes;

    /** 2 枚目以降のタグの行に使う商品 (旧 Clothes::MULTI_TAG_CLOTHES_ID。本番では論理削除済みの「〃」)。 */
    public const MULTI_TAG_CLOTHES_ID = 999;

    protected $table = 'clothes';

    protected $guarded = ['id'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
