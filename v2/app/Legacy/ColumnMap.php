<?php

namespace App\Legacy;

/**
 * 旧 DB (Laravel 8) → v2 のテーブル名・カラム名の対応表。
 *
 * 綴りの修正だけを入れ、それ以外は旧と同じ名前・同じ値で移す (ID も旧のまま)。
 *   customers.needs_payment_confimation → needs_payment_confirmation
 *   customers.needs_return_confimation  → needs_return_confirmation
 *   order_clothes                       → order_items
 *   tag_numbers                         → tag_counters (tag_number → value)
 *   ipaddresses.ipaddress (id=1)        → settings('printer_ip')   ※テーブルではなく設定値 (SETTINGS)
 *   *.discount_raito                    → discount_ratio           ※旧 DB にこのカラムは無い (API の応答キーだけ)。
 *                                                                    該当カラムが現れたら import が止まる (未知のカラム検査)
 *
 * columns は旧テーブルの全カラムを列挙する (旧 => v2)。旧 DB に表にないカラムがある / 表のカラムが無いときは
 * import が止まる。並びは投入順 (外部キーの親が先)。
 *
 * @phpstan-type TableMap array{table: string, columns: array<string, string>}
 */
final class ColumnMap
{
    /** @var array<string, array{table: string, columns: array<string, string>}> */
    public const TABLES = [
        'stores' => ['table' => 'stores', 'columns' => [
            'id' => 'id', 'name' => 'name', 'email' => 'email', 'password' => 'password',
            'postal_code' => 'postal_code', 'address' => 'address', 'phone_number' => 'phone_number',
            'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'managers' => ['table' => 'managers', 'columns' => [
            'id' => 'id', 'store_id' => 'store_id', 'name' => 'name',
            'theme_header' => 'theme_header', 'theme_body' => 'theme_body',
            'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'customers' => ['table' => 'customers', 'columns' => [
            'id' => 'id', 'manager_id' => 'manager_id', 'name' => 'name', 'name_kana' => 'name_kana',
            'phone_number' => 'phone_number', 'birth_day' => 'birth_day', 'sex' => 'sex', 'point' => 'point',
            'cutoff_date' => 'cutoff_date', 'is_invoice' => 'is_invoice',
            'needs_payment_confimation' => 'needs_payment_confirmation',
            'needs_return_confimation' => 'needs_return_confirmation',
            'total_sales' => 'total_sales', 'number_of_visits' => 'number_of_visits',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'customer_information' => ['table' => 'customer_information', 'columns' => [
            'id' => 'id', 'customer_id' => 'customer_id', 'information' => 'information',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'invoices' => ['table' => 'invoices', 'columns' => [
            'id' => 'id', 'manager_id' => 'manager_id', 'customer_id' => 'customer_id',
            'period_start' => 'period_start', 'period_end' => 'period_end', 'paid_at' => 'paid_at',
            'issued_at' => 'issued_at', 'has_carried_over' => 'has_carried_over', 'carry_over_id' => 'carry_over_id',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'orders' => ['table' => 'orders', 'columns' => [
            'id' => 'id', 'store_id' => 'store_id', 'manager_id' => 'manager_id', 'customer_id' => 'customer_id',
            'invoice_id' => 'invoice_id', 'is_invoice' => 'is_invoice', 'amount' => 'amount',
            'reduction' => 'reduction', 'discount' => 'discount', 'payment' => 'payment',
            'paid_at' => 'paid_at', 'handed_at' => 'handed_at', 'note' => 'note',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'categories' => ['table' => 'categories', 'columns' => [
            'id' => 'id', 'store_id' => 'store_id', 'name' => 'name',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'clothes' => ['table' => 'clothes', 'columns' => [
            'id' => 'id', 'store_id' => 'store_id', 'category_id' => 'category_id', 'sort_key' => 'sort_key',
            'name' => 'name', 'name_kana' => 'name_kana', 'price' => 'price', 'tag_count' => 'tag_count',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'order_clothes' => ['table' => 'order_items', 'columns' => [
            'id' => 'id', 'order_id' => 'order_id', 'clothes_id' => 'clothes_id', 'tag' => 'tag',
            'handed_at' => 'handed_at',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'tag_numbers' => ['table' => 'tag_counters', 'columns' => [
            'id' => 'id', 'manager_id' => 'manager_id', 'tag_number' => 'value',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'taxes' => ['table' => 'taxes', 'columns' => [
            'id' => 'id', 'store_id' => 'store_id', 'tax' => 'tax',
            'deleted_at' => 'deleted_at', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'delivery_customers' => ['table' => 'delivery_customers', 'columns' => [
            'id' => 'id', 'name' => 'name', 'email' => 'email', 'registration_number' => 'registration_number',
            'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'delivery_departments' => ['table' => 'delivery_departments', 'columns' => [
            'id' => 'id', 'delivery_customer_id' => 'delivery_customer_id', 'name' => 'name',
            'sort_order' => 'sort_order', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'delivery_products' => ['table' => 'delivery_products', 'columns' => [
            'id' => 'id', 'delivery_department_id' => 'delivery_department_id', 'name' => 'name',
            'unit_price' => 'unit_price', 'tax_rate' => 'tax_rate', 'sort_order' => 'sort_order',
            'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'delivery_notes' => ['table' => 'delivery_notes', 'columns' => [
            'id' => 'id', 'delivery_customer_id' => 'delivery_customer_id', 'period_start' => 'period_start',
            'period_end' => 'period_end', 'note_number' => 'note_number',
            'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'delivery_daily_entries' => ['table' => 'delivery_daily_entries', 'columns' => [
            'id' => 'id', 'delivery_product_id' => 'delivery_product_id', 'delivery_note_id' => 'delivery_note_id',
            'date' => 'date', 'quantity' => 'quantity', 'note' => 'note',
            'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
        'delivery_email_logs' => ['table' => 'delivery_email_logs', 'columns' => [
            'id' => 'id', 'delivery_note_id' => 'delivery_note_id', 'delivery_customer_id' => 'delivery_customer_id',
            'period_start' => 'period_start', 'period_end' => 'period_end', 'sent_to' => 'sent_to',
            'sent_at' => 'sent_at', 'status' => 'status', 'created_at' => 'created_at', 'updated_at' => 'updated_at',
        ]],
    ];

    /**
     * 行ではなく設定値に移すもの: 旧テーブル => [カラム, 行 id, settings のキー]。
     * 旧アプリは Ipaddress::find(1) / where('id', 1) だけを使う。
     *
     * @var array<string, array{column: string, id: int, key: string}>
     */
    public const SETTINGS = [
        'ipaddresses' => ['column' => 'ipaddress', 'id' => 1, 'key' => 'printer_ip'],
    ];

    /** 移さない旧テーブル (ログイン状態・API トークン・ブラウザのエラーログ・旧のマイグレーション履歴)。 */
    public const SKIPPED = ['sessions', 'personal_access_tokens', 'client_errors', 'migrations'];

    /** v2 で追加したカラム: v2 テーブル => [カラム => 由来]。値は import が決める (旧には無い)。 */
    public const ADDED = [
        'delivery_customers' => ['store_id' => 'legacy_store_min_id'],
    ];

    /** @return list<string> --only=delivery の対象 (旧テーブル名) */
    public static function deliveryTables(): array
    {
        return array_values(array_filter(array_keys(self::TABLES), fn (string $t) => str_starts_with($t, 'delivery_')));
    }
}
