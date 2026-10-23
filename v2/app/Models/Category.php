<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** 商品カテゴリ (旧 categories)。 */
class Category extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'categories';

    protected $guarded = ['id'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function clothes(): HasMany
    {
        return $this->hasMany(Clothes::class);
    }
}
