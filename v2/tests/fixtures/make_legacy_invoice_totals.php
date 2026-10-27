<?php

/*
 * 旧アプリの請求書 PDF の元データ (旧 InvoiceController::generate が invoice/pdf.blade.php に渡す
 * Invoice::fetchPrintInvoices の戻り値と税率) から tests/fixtures/legacy_invoice_totals.json を作る
 * (InvoicePdfTest::test_invoice_pdf_amounts_match_legacy の期待値)。
 *
 * 旧アプリ (Laravel 8) の上で動かす。本番ではなく、ローカルの旧 Docker (docker-compose.legacy.yml、本番ダンプ入り) で:
 *   docker cp v2/tests/fixtures/make_legacy_invoice_totals.php cleaning_pos_legacy-app-1:/tmp/
 *   docker cp v2/tests/Support/invoice_pdf_summary.php cleaning_pos_legacy-app-1:/tmp/
 *   docker exec cleaning_pos_legacy-app-1 php /tmp/make_legacy_invoice_totals.php /var/www/html > v2/tests/fixtures/legacy_invoice_totals.json
 *
 * 1 つのトランザクションの中で次を行い、最後に必ずロールバックする (旧 DB には何も残さない):
 *   1. 個人情報を消す: 対象の顧客名を「請求テスト{id}」、カナ・電話・誕生日・注文メモ・店舗と担当者の情報を架空の値に書き換える
 *   2. 本番には繰越の実例が無い (has_carried_over = 0 が全件) ので、旧の Api\InvoiceController::carry_over を
 *      そのまま呼んで請求 CARRY_FROM を翌期間の CARRY_TO へ繰り越す
 *   3. fetchPrintInvoices(PRINT_IDS) と税率 (旧と同じ Tax::where('store_id', 1)) を読み、Blade と同じ式で消費税を出す
 *   4. その状態の旧の行 (v2 で legacy:import して同じ請求書を作るための最小限) を書き出す
 *
 * 対象 (2026-10 の本番ダンプ):
 *   1347 … 入金確認ありの顧客 (締め日 25)、646 点で複数ページ
 *   1383 … 入金確認なしの顧客 (締め日 99)。1368 (前月) の繰越を受ける
 *   1397 … 入金確認なしの顧客、1 伝票 115 点で複数ページ
 * 印刷順は ID 順ではなく ids の並び (FIELD) になることも見るため、わざと並べ替えている。
 */

// use は先頭に置く (PHP の use はそれより後ろのコードにだけ効く)
use App\Http\Controllers\Api\InvoiceController;
use App\Models\Invoice;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$legacyRoot = $argv[1] ?? '/var/www/html';
require $legacyRoot.'/vendor/autoload.php';
require __DIR__.'/invoice_pdf_summary.php';

$app = require $legacyRoot.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

const PRINT_IDS = '1397,1347,1383';
const CARRY_FROM = 1368;
const CARRY_TO = 1383;

/** テーブルの行を {columns, rows} で (削除済みも含めて) 読む */
function dumpRows(string $table, string $column, array $ids): array
{
    $rows = DB::table($table)->whereIn($column, array_values(array_unique($ids)))->orderBy('id')->get()
        ->map(fn ($r) => (array) $r)->all();

    return ['columns' => array_keys($rows[0] ?? []), 'rows' => array_map('array_values', $rows)];
}

$conn = DB::connection();
$conn->beginTransaction();
try {
    $printIds = array_map('intval', explode(',', PRINT_IDS));

    // 繰越元を含めた、データを書き出す請求書
    $invoiceIds = array_merge($printIds, [CARRY_FROM]);
    $orderIds = DB::table('orders')->whereIn('invoice_id', $invoiceIds)->pluck('id')->all();
    $customerIds = array_unique(array_merge(
        DB::table('invoices')->whereIn('id', $invoiceIds)->pluck('customer_id')->all(),
        DB::table('orders')->whereIn('id', $orderIds)->pluck('customer_id')->all(),
    ));
    $managerIds = array_unique(array_merge(
        DB::table('invoices')->whereIn('id', $invoiceIds)->pluck('manager_id')->all(),
        DB::table('orders')->whereIn('id', $orderIds)->pluck('manager_id')->all(),
        DB::table('customers')->whereIn('id', $customerIds)->pluck('manager_id')->all(),
    ));

    // 1. 個人情報を架空の値にする (ロールバックで元に戻る)
    foreach ($customerIds as $id) {
        DB::table('customers')->where('id', $id)->update([
            'name' => '請求テスト'.$id, 'name_kana' => 'ｾｲｷｭｳﾃｽﾄ', 'phone_number' => null, 'birth_day' => null, 'updated_at' => DB::raw('updated_at'),
        ]);
    }
    DB::table('orders')->whereIn('id', $orderIds)->update(['note' => null, 'updated_at' => DB::raw('updated_at')]);
    foreach ($managerIds as $id) {
        DB::table('managers')->where('id', $id)->update(['name' => '担当'.$id, 'updated_at' => DB::raw('updated_at')]);
    }
    DB::table('stores')->update([
        'name' => 'テスト店', 'email' => DB::raw("CONCAT('store', id, '@example.com')"), 'password' => 'x',
        'postal_code' => '000-0000', 'address' => 'テスト県', 'phone_number' => '0000-00-0000', 'updated_at' => DB::raw('updated_at'),
    ]);

    // 2. 旧の繰越処理をそのまま呼ぶ (更新日時を固定して、何度作り直しても同じ JSON にする)
    Carbon::setTestNow('2026-10-27 00:00:00');
    app(InvoiceController::class)->carry_over(new Request(['invoices' => [CARRY_FROM]]), new Invoice);
    $carried = DB::table('invoices')->where('id', CARRY_FROM)->value('carry_over_id');
    if ((int) $carried !== CARRY_TO) {
        throw new RuntimeException('繰越先が '.CARRY_TO." ではない: {$carried}");
    }

    // 3. 旧 InvoiceController::generate と同じ読み方
    $pages = (new Invoice)->fetchPrintInvoices(PRINT_IDS);
    $tax = Tax::where('store_id', 1)->value('tax');
    foreach ($pages as $i => $page) {
        // 旧 Blade: number_format($invoice['amount'] - round($invoice['amount'] / (1 + $tax / 100)))
        $pages[$i]['tax_amount'] = (int) ($page['amount'] - round($page['amount'] / (1 + $tax / 100)));
    }

    // 4. v2 に取り込む旧の行
    $orderItemRows = dumpRows('order_clothes', 'order_id', $orderIds);
    $clothesIds = array_column(DB::table('order_clothes')->whereIn('order_id', $orderIds)->get(['clothes_id'])->map(fn ($r) => (array) $r)->all(), 'clothes_id');
    $clothesRows = dumpRows('clothes', 'id', $clothesIds);
    $categoryIds = DB::table('clothes')->whereIn('id', $clothesIds)->pluck('category_id')->all();
    $storeIds = DB::table('managers')->whereIn('id', $managerIds)->pluck('store_id')->all();

    $out = [
        'generated_by' => 'App\\Models\\Invoice::fetchPrintInvoices + invoice/pdf.blade.php tax (legacy, PHP '.PHP_VERSION.'), '
            .'local legacy Docker (production dump), carry_over '.CARRY_FROM.' -> '.CARRY_TO.' inside a rolled-back transaction',
        'print_ids' => $printIds,
        'tax' => (int) $tax,
        'expected' => invoice_pdf_summary($pages),
        'legacy_rows' => [
            'stores' => dumpRows('stores', 'id', $storeIds),
            'managers' => dumpRows('managers', 'id', $managerIds),
            'customers' => dumpRows('customers', 'id', $customerIds),
            'invoices' => dumpRows('invoices', 'id', array_merge($invoiceIds, DB::table('invoices')->whereIn('carry_over_id', $invoiceIds)->pluck('id')->all())),
            'orders' => dumpRows('orders', 'id', $orderIds),
            'categories' => dumpRows('categories', 'id', $categoryIds),
            'clothes' => $clothesRows,
            'order_clothes' => $orderItemRows,
            'taxes' => dumpRows('taxes', 'store_id', $storeIds),
        ],
    ];
} finally {
    $conn->rollBack();
}

// 値だけの配列 (明細 1 行・DB の 1 行) は 1 行にまとめる (JSON の文字列は改行を含まないので空白を詰めるだけ)
$json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
echo preg_replace_callback('/\[[^\[\]{}]*\]/', fn ($m) => preg_replace('/\n\s*/', '', $m[0]), $json)."\n";
