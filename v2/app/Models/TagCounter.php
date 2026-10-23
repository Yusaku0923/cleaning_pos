<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** 担当者ごとのタグ番号の連番 (旧 tag_numbers、tag_number → value)。店舗は担当者経由。 */
class TagCounter extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'tag_counters';

    protected $guarded = ['id'];

    public static function storeRelation(): ?string
    {
        return 'manager';
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }
}
