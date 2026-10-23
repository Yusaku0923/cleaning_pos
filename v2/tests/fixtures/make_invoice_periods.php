<?php

/*
 * 旧 App\Services\Utility::searchInvoicePeriod の実際の戻り値から
 * tests/fixtures/invoice_periods.json を作る (InvoicePeriodTest の期待値)。
 *
 * 旧アプリ (Laravel 8) の上で動かす。旧アプリの Docker で:
 *   docker cp v2/tests/fixtures/make_invoice_periods.php cleaning_pos_legacy-app-1:/tmp/
 *   docker exec cleaning_pos_legacy-app-1 php /tmp/make_invoice_periods.php /var/www/html > v2/tests/fixtures/invoice_periods.json
 *
 * 出力の各行:
 *   start_raw / end_raw  … 旧関数が返した文字列そのまま (例: "2026-02-31", "2026-07-0", "2026-02-6")
 *   start / end          … その文字列を旧の PHP が日付として読んだ値 date('Y-m-d', strtotime(raw))。
 *                           旧の繰越処理 (InvoiceController::carry_over) も strtotime で読む。
 */

$legacyRoot = $argv[1] ?? '/var/www/html';
require $legacyRoot.'/vendor/autoload.php';

use App\Services\Utility;

// 締め日: タスク指定の 8 種 + 本番に実在する 0 (未選択) / 1 / 15
$cutoffs = [5, 20, 25, 28, 29, 30, 31, 99, 0, 1, 15];
// 渡し日: タスク指定の 5 日 + 本番で日付 00 が出た日 (請求書 1281 / 1289)
$dates = ['2026-01-15', '2026-02-10', '2026-02-28', '2026-03-31', '2026-12-31', '2026-06-23', '2026-07-01'];

$rows = [];
foreach ($cutoffs as $cutoff) {
    foreach ($dates as $date) {
        [$start, $end] = Utility::searchInvoicePeriod($cutoff, $date);
        $rows[] = [
            'cutoff' => $cutoff,
            'handed_at' => $date,
            'start_raw' => $start,
            'end_raw' => $end,
            'start' => date('Y-m-d', strtotime($start)),
            'end' => date('Y-m-d', strtotime($end)),
        ];
    }
}

echo json_encode([
    'generated_by' => 'App\\Services\\Utility::searchInvoicePeriod (legacy, PHP '.PHP_VERSION.')',
    'cases' => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
