<?php

// 店の業務ルール (global-constraints / spec §11 の仮決め)。
return [
    // 税の端数処理: 'round' (四捨五入) | 'floor' (切り捨て)。画面表示は請求書と同じ。
    'tax_rounding' => [
        'invoice' => env('POS_TAX_ROUNDING_INVOICE', 'round'),
        'delivery' => env('POS_TAX_ROUNDING_DELIVERY', 'floor'),
    ],

    // タグ番号: 数値で 1000〜10999。10999 の次は 1000。
    'tag' => [
        'min' => 1000,
        'max' => 10999,
    ],
];
