<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * 顧客表示への通知 (旧 App\Events\CustomerDisplay)。
 *
 * イベント名 `customer-display` とペイロード `{message: {...}}` は旧と同じ
 * (画面は `.listen('.customer-display', req => req.message)`)。
 * チャンネルだけ旧の公開 `cleaning-pos` から店舗ごとの private `private-store.{storeId}.display` に変えた。
 */
class CustomerDisplayUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<string, mixed>  $message  旧と同じ {event: 'customer'|'order'|'update'|'delete'|'discount'|'account'|'finish', ...}
     */
    public function __construct(public array $message, protected int $storeId) {}

    public static function channelName(int $storeId): string
    {
        return "store.{$storeId}.display";
    }

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(self::channelName($this->storeId))];
    }

    public function broadcastAs(): string
    {
        return 'customer-display';
    }

    /** 旧はイベントの public プロパティ ($message) がそのまま送られていた。同じ形にする */
    public function broadcastWith(): array
    {
        return ['message' => $this->message];
    }
}
