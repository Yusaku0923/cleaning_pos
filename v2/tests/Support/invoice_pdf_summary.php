<?php

/*
 * 請求書 PDF の Blade (旧 resources/views/invoice/pdf.blade.php / v2 invoice/pdf.blade.php) に渡すページ配列から、
 * 紙に出る値だけを取り出す。旧と v2 で同じ関数を使って比べる (旧の Docker では
 * tests/fixtures/make_legacy_invoice_totals.php が require する。依存なしの素の PHP)。
 *
 * 各ページ ($pages の 1 要素) のキー: id, page_count, customer_name, period_end, amount, carried_over_amount,
 * tax_amount (消費税。旧は Blade の式 amount - round(amount / (1 + tax/100)))、row (明細行)。
 * 日付は Blade と同じく date(..., strtotime(...)) で読む (PHP の既定タイムゾーンはアプリと同じ Asia/Tokyo)。
 */
if (! function_exists('invoice_pdf_summary')) {
    function invoice_pdf_summary(array $pages): array
    {
        $pagesPerInvoice = array_count_values(array_column($pages, 'id'));
        $out = [];
        foreach ($pages as $page) {
            $total = $pagesPerInvoice[$page['id']];
            $first = (int) $page['page_count'] === 1;
            $rows = [];
            foreach ($page['row'] as $row) {
                $rows[] = match ($row['is_detail']) {
                    1 => ['d', date('y/m/d', strtotime($row['handed_at'])), (int) $row['order_id'],
                        $row['tag'] !== '0-000' ? $row['tag'] : '', $row['name'], (int) $row['price'], (int) $row['count'], (int) $row['price']],
                    2 => ['g', date('y/m/d', strtotime($row['handed_at'])), (int) $row['order_id'],
                        $row['tag'] !== '0-000' ? $row['start_tag'].'~'.$row['end_tag'] : '', $row['name'], (int) $row['price'], (int) $row['count'],
                        (int) $row['price'] * (int) $row['count']],
                    default => ['o', count($row['items']), (int) $row['amount']],
                };
            }
            $out[] = [
                'id' => (int) $page['id'],
                'page' => (int) $page['page_count'].'/'.$total,
                'customer_name' => $page['customer_name'],
                'invoice_no' => str_pad((string) $page['id'], 5, '0', STR_PAD_LEFT).'-'.date('ymd', strtotime($page['period_end'])),
                'cutoff' => date('Y/m/d', strtotime($page['period_end'])),
                // 1 ページ目だけ金額が出る (2 ページ目以降は 0)
                'carried_over_amount' => $first ? (int) $page['carried_over_amount'] : 0,
                'amount' => $first ? (int) $page['amount'] : 0,
                'billed' => $first ? (int) $page['amount'] + (int) $page['carried_over_amount'] : 0,
                // 「10%対象」「消費税」の行: 30 行未満のページか最後のページだけ (金額は請求書全体)
                'tax_line' => (count($page['row']) < 30 || (int) $page['page_count'] === $total)
                    ? ['amount' => (int) $page['amount'], 'tax' => (int) $page['tax_amount']]
                    : null,
                'rows' => $rows,
            ];
        }

        return $out;
    }
}
