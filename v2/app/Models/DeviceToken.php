<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * 店の端末の自動ログイン用トークン (GET /login/device/{token})。
 * 平文は発行時に1回だけ表示し、DB には SHA-256 だけを持つ。
 */
class DeviceToken extends Model
{
    protected $fillable = ['store_id', 'name', 'token_hash', 'last_used_at', 'revoked_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * @return array{0: self, 1: string} [保存した行, 平文トークン]
     */
    public static function issue(Store $store, string $name): array
    {
        $plain = Str::random(48);
        $device = static::create([
            'store_id' => $store->id,
            'name' => $name,
            'token_hash' => static::hash($plain),
        ]);

        return [$device, $plain];
    }

    public static function findActive(string $plain): ?self
    {
        return static::query()
            ->where('token_hash', static::hash($plain))
            ->whereNull('revoked_at')
            ->first();
    }
}
