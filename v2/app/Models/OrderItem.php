<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** 受付の品物 1 点 = タグ 1 つ (旧 order_clothes)。tag は旧の文字列のまま。店舗は受付経由。 */
class OrderItem extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'order_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'handed_at' => 'datetime',
        ];
    }

    public static function storeRelation(): ?string
    {
        return 'order';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function clothes(): BelongsTo
    {
        return $this->belongsTo(Clothes::class, 'clothes_id');
    }
}
