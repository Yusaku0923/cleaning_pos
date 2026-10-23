<?php

namespace App\Services;

use App\Events\CustomerDisplayUpdated;
use App\Models\DisplayStore;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Support\Str;
use Throwable;

/**
 * 顧客表示 (旧 Utility::sendWebSocket と顧客表示端末のトークン)。
 */
class CustomerDisplay
{
    public const SETTING_KEY = 'display_token';

    /**
     * 顧客表示へ送る。Pusher が未設定・不通でも POS の操作は止めない (失敗はログに残すだけ)。
     *
     * @param  array<string, mixed>  $message
     */
    public static function push(int $storeId, array $message): void
    {
        // Pusher の鍵が無い (未設定の環境) ときは送らない。毎回エラーを残さない
        if (config('broadcasting.default') === 'pusher' && empty(config('broadcasting.connections.pusher.key'))) {
            return;
        }
        try {
            event(new CustomerDisplayUpdated($message, $storeId));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * 表示端末のトークンを発行する (前のトークンは無効になる)。settings には SHA-256 だけを持つ。
     *
     * @return string 平文トークン (表示はこの1回だけ)
     */
    public static function issueToken(Store $store): string
    {
        $plain = Str::random(48);
        Setting::set(self::SETTING_KEY, ['hash' => hash('sha256', $plain), 'store_id' => $store->id]);
        // ログインを保持している表示端末も切る (古い URL で入った端末を残さない)
        Store::query()->whereKey($store->id)->update(['remember_token' => null]);

        return $plain;
    }

    /** トークンが一致すれば、その店舗を返す (比較は hash_equals) */
    public static function storeForToken(string $plain): ?DisplayStore
    {
        $stored = Setting::get(self::SETTING_KEY);
        if (! is_array($stored) || ! is_string($stored['hash'] ?? null) || ! isset($stored['store_id'])) {
            return null;
        }
        if (! hash_equals($stored['hash'], hash('sha256', $plain))) {
            return null;
        }

        return DisplayStore::query()->find($stored['store_id']);
    }
}
