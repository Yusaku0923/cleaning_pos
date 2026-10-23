<?php

namespace App\Console\Commands;

use App\Legacy\ColumnMap;
use App\Legacy\LegacyReader;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 旧 DB と v2 DB を突き合わせる。全部一致なら終了コード 0、1 つでも違えば 1。
 *  - テーブルごとの件数 (論理削除の行も含む) と、全カラムの値のチェックサム (id 順、ColumnMap の対応で比較)
 *  - orders_total_sum (論理削除されていない orders.amount の合計)
 *  - invoices_unpaid_count (論理削除されていない請求書のうち paid_at が空のもの)
 *  - tag_counters (manager_id → value) と旧 tag_numbers (manager_id → tag_number)
 *  - settings('printer_ip') と旧 ipaddresses(id=1)
 *  - delivery_customers.store_id (旧の店舗の最小 id)
 */
class LegacyVerify extends Command
{
    protected $signature = 'legacy:verify';

    protected $description = '旧 DB と v2 DB の件数・値・合計を突き合わせる (一致で 0、不一致で 1)';

    private bool $ok = true;

    public function handle(): int
    {
        $reader = new LegacyReader;

        $rows = [];
        foreach (ColumnMap::TABLES as $legacyTable => $map) {
            try {
                $legacyCount = (int) $reader->scalar("select count(*) from `{$legacyTable}`");
                $v2Count = DB::table($map['table'])->count();
                $same = $legacyCount === $v2Count
                    && $this->legacyChecksum($reader, $legacyTable, $map) === $this->v2Checksum($map);
                $rows[] = [$legacyTable, $map['table'], $legacyCount, $v2Count, $this->mark($same)];
            } catch (Throwable $e) {
                $rows[] = [$legacyTable, $map['table'], '?', '?', $this->mark(false).' '.$e->getMessage()];
            }
        }
        $this->table(['legacy_table', 'v2_table', 'legacy_count', 'v2_count', 'values'], $rows);

        $totals = [];
        $totals[] = $this->compare(
            'orders_total_sum',
            (int) $reader->scalar('select coalesce(sum(amount), 0) from `orders` where deleted_at is null'),
            (int) DB::table('orders')->whereNull('deleted_at')->sum('amount')
        );
        $totals[] = $this->compare(
            'invoices_unpaid_count',
            (int) $reader->scalar('select count(*) from `invoices` where paid_at is null and deleted_at is null'),
            DB::table('invoices')->whereNull('paid_at')->whereNull('deleted_at')->count()
        );
        foreach (ColumnMap::SETTINGS as $legacyTable => $s) {
            $totals[] = $this->compare(
                "settings.{$s['key']}",
                $reader->scalar(sprintf('select `%s` from `%s` where id = ?', $s['column'], $legacyTable), [$s['id']]),
                Setting::get($s['key'])
            );
        }
        $legacyStore = $reader->scalar('select min(id) from `stores`');
        $v2Stores = DB::table('delivery_customers')->distinct()->pluck('store_id')->map(fn ($v) => (int) $v)->all();
        $totals[] = $this->compare(
            'delivery_customers.store_id',
            $v2Stores === [] ? '-' : (string) $legacyStore,
            $v2Stores === [] ? '-' : implode(',', $v2Stores)
        );
        $this->table(['check', 'legacy', 'v2', 'ok'], $totals);

        $legacyTags = collect($reader->select('select manager_id, tag_number from `tag_numbers` where deleted_at is null order by manager_id'))
            ->mapWithKeys(fn ($r) => [(int) $r->manager_id => (int) $r->tag_number]);
        $v2Tags = DB::table('tag_counters')->whereNull('deleted_at')->orderBy('manager_id')->pluck('value', 'manager_id')
            ->mapWithKeys(fn ($v, $k) => [(int) $k => (int) $v]);
        $tagRows = [];
        foreach ($legacyTags->keys()->merge($v2Tags->keys())->unique()->sort() as $managerId) {
            $tagRows[] = $this->compare((string) $managerId, $legacyTags->get($managerId, '-'), $v2Tags->get($managerId, '-'));
        }
        $this->line('tag_counters (manager_id → value)');
        $this->table(['manager_id', 'legacy tag_number', 'v2 value', 'ok'], $tagRows);

        if ($this->ok) {
            $this->info('OK: 旧 DB と v2 DB は一致しています。');

            return self::SUCCESS;
        }
        $this->error('NG: 一致しない項目があります。');

        return self::FAILURE;
    }

    /** @return array{0: string, 1: mixed, 2: mixed, 3: string} */
    private function compare(string $label, mixed $legacy, mixed $v2): array
    {
        return [$label, $legacy ?? 'NULL', $v2 ?? 'NULL', $this->mark($legacy === $v2)];
    }

    private function mark(bool $same): string
    {
        if (! $same) {
            $this->ok = false;
        }

        return $same ? 'OK' : 'NG';
    }

    /** @param array{table: string, columns: array<string, string>} $map */
    private function legacyChecksum(LegacyReader $reader, string $legacyTable, array $map): string
    {
        return $this->checksum($reader->rows($legacyTable, array_keys($map['columns'])), array_keys($map['columns']));
    }

    /** @param array{table: string, columns: array<string, string>} $map */
    private function v2Checksum(array $map): string
    {
        $columns = array_values($map['columns']);

        return $this->checksum(DB::table($map['table'])->select($columns)->lazyById(1000, 'id'), $columns);
    }

    /** 行を id 順に並べ、ColumnMap の順のカラム値を JSON にしてつなげた md5。型の違い (int/string) は文字列にそろえる。 */
    private function checksum(iterable $rows, array $columns): string
    {
        $ctx = hash_init('md5');
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $c) {
                $v = $row->{$c};
                $values[] = $v === null ? null : (string) $v;
            }
            hash_update($ctx, json_encode($values, JSON_UNESCAPED_UNICODE)."\n");
        }

        return hash_final($ctx);
    }
}
