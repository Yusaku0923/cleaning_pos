<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ClientError extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_type',
        'message',
        'stack',
        'url',
        'user_agent',
        'manager_id',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];

    /**
     * エラーを DB に保存する。10% の確率で 30日超レコードを削除する。
     * DB 保存失敗時はサイレントに無視（ファイルログに記録）。
     */
    public static function log(array $data): void
    {
        try {
            if (random_int(1, 10) === 1) {
                try {
                    static::pruneExpired();
                } catch (\Throwable $e) {
                    Log::error('ClientError::pruneExpired failed: ' . $e->getMessage());
                }
            }
            static::create($data);
        } catch (\Throwable $e) {
            Log::error('ClientError::log failed: ' . $e->getMessage());
        }
    }

    /**
     * 30日より古いレコードを削除する。
     */
    public static function pruneExpired(): void
    {
        static::where('created_at', '<', now()->subDays(30))->delete();
    }
}
