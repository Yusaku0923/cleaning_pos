<?php

use App\Models\Store;
use Illuminate\Support\Facades\Broadcast;

// 顧客表示: 表示端末 (display ガード) が自分の店舗のチャンネルだけ購読できる
Broadcast::channel('store.{storeId}.display', function (Store $store, $storeId) {
    return (int) $store->id === (int) $storeId;
}, ['guards' => ['display']]);
