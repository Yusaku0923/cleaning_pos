<?php

namespace App\Console\Commands;

use App\Legacy\ColumnMap;
use App\Legacy\LegacyReader;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * 旧 DB (legacy 接続、読むだけ) → v2 DB (既定の接続) に全件を移す。
 * ID・値は旧のまま (計算し直さない)。名前の対応は App\Legacy\ColumnMap。
 */
class LegacyImport extends Command
{
    protected $signature = 'legacy:import
        {--fresh : v2 DB を migrate:fresh してから全件を入れる}
        {--only= : delivery を指定すると delivery_* テーブルだけを入れ直す}';

    protected $description = '旧 DB から v2 DB へデータを移す (旧 DB は読むだけ)';

    private const CHUNK = 1000;

    public function handle(): int
    {
        try {
            $this->assertSafeTarget();
            $reader = new LegacyReader;
            $this->assertLegacySchema($reader);

            $only = $this->option('only');
            if ($only !== null && $only !== 'delivery') {
                throw new RuntimeException("--only に指定できるのは delivery だけです: {$only}");
            }
            if ($only === 'delivery') {
                if ($this->option('fresh')) {
                    throw new RuntimeException('--only=delivery と --fresh は同時に使えません (--fresh は全テーブルを消す)。');
                }

                return $this->importTables($reader, ColumnMap::deliveryTables(), replace: true);
            }

            $target = $this->targetConnection();
            if ($this->option('fresh')) {
                $this->call('migrate:fresh', ['--force' => true, '--database' => $target]);
            } else {
                $this->call('migrate', ['--force' => true, '--database' => $target]);
                foreach (ColumnMap::TABLES as $map) {
                    if (DB::table($map['table'])->exists()) {
                        throw new RuntimeException("v2 の {$map['table']} にデータがあります。入れ直すときは --fresh を付けてください。");
                    }
                }
            }

            return $this->importTables($reader, array_keys(ColumnMap::TABLES), replace: false);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function targetConnection(): string
    {
        return (string) config('database.default');
    }

    /** 書き込み先が旧 DB そのものなら何もせずに止める。 */
    private function assertSafeTarget(): void
    {
        $target = $this->targetConnection();
        $legacyDb = LegacyReader::databaseName();
        $targetDb = (string) config("database.connections.{$target}.database");

        if ($target === LegacyReader::CONNECTION) {
            throw new RuntimeException('既定の接続が legacy です。旧 DB には書き込みません。');
        }
        if ($legacyDb === '') {
            throw new RuntimeException('LEGACY_DB_DATABASE が設定されていません。');
        }
        if (strcasecmp($legacyDb, $targetDb) === 0) {
            throw new RuntimeException("書き込み先 ({$target}: {$targetDb}) が旧 DB と同じ名前です。中止しました。");
        }
    }

    /** 旧 DB のテーブル・カラムが ColumnMap と完全に一致することを確かめる (知らないカラムは黙って捨てない)。 */
    private function assertLegacySchema(LegacyReader $reader): void
    {
        $tables = $reader->tables();
        $known = array_merge(array_keys(ColumnMap::TABLES), array_keys(ColumnMap::SETTINGS), ColumnMap::SKIPPED);

        $unknown = array_diff($tables, $known);
        if ($unknown !== []) {
            throw new RuntimeException('ColumnMap に無い旧テーブルがあります: '.implode(', ', $unknown));
        }
        $missing = array_diff(array_keys(ColumnMap::TABLES), $tables);
        if ($missing !== []) {
            throw new RuntimeException('旧 DB に無いテーブルがあります: '.implode(', ', $missing));
        }
        foreach (ColumnMap::TABLES as $legacyTable => $map) {
            $actual = $reader->columns($legacyTable);
            $expected = array_keys($map['columns']);
            if (array_diff($actual, $expected) !== [] || array_diff($expected, $actual) !== []) {
                throw new RuntimeException(sprintf(
                    '旧 %s のカラムが ColumnMap と違います。旧: [%s] / 表: [%s]',
                    $legacyTable, implode(', ', $actual), implode(', ', $expected)
                ));
            }
        }
    }

    /**
     * @param  list<string>  $legacyTables  投入順 (外部キーの親が先)
     * @param  bool  $replace  先に v2 側の行を消す (--only=delivery)
     */
    private function importTables(LegacyReader $reader, array $legacyTables, bool $replace): int
    {
        $deliveryStoreId = null;
        if (in_array('delivery_customers', $legacyTables, true)) {
            // 旧の納品書は店舗を持たない。旧の店舗 (本番は 1 件) のうち最小 id に属させる
            $deliveryStoreId = $reader->scalar('select min(id) from `stores`');
            if ($deliveryStoreId === null && $reader->scalar('select count(*) from `delivery_customers`') > 0) {
                throw new RuntimeException('旧 DB に stores が無いため delivery_customers.store_id を決められません。');
            }
        }

        $counts = [];
        // 旧本番には '2026-07-00' のような「日が 00」の日付が実在する (旧の sql_mode に NO_ZERO_IN_DATE が無い)。
        // 値を変えずに移すため、投入中だけ v2 側のセッションからも NO_ZERO_IN_DATE / NO_ZERO_DATE を外す
        $sqlMode = (string) DB::scalar('select @@session.sql_mode');
        $lenient = implode(',', array_filter(
            explode(',', $sqlMode),
            fn ($m) => ! in_array($m, ['NO_ZERO_IN_DATE', 'NO_ZERO_DATE'], true)
        ));
        DB::statement('set session sql_mode = ?', [$lenient]);
        Schema::disableForeignKeyConstraints();
        try {
            DB::transaction(function () use ($reader, $legacyTables, $replace, $deliveryStoreId, &$counts) {
                if ($replace) {
                    foreach (array_reverse($legacyTables) as $legacyTable) {
                        DB::table(ColumnMap::TABLES[$legacyTable]['table'])->delete();
                    }
                }
                foreach ($legacyTables as $legacyTable) {
                    $map = ColumnMap::TABLES[$legacyTable];
                    $extra = $legacyTable === 'delivery_customers' ? ['store_id' => $deliveryStoreId] : [];
                    $counts[$legacyTable] = $this->copyTable($reader, $legacyTable, $map, $extra);
                }
                if (! $replace) {
                    $this->copySettings($reader);
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
            DB::statement('set session sql_mode = ?', [$sqlMode]);
        }

        // 旧で物理削除された行の ID を使い回さないよう、AUTO_INCREMENT も旧に合わせる (DDL なのでトランザクションの外)
        foreach ($legacyTables as $legacyTable) {
            $ai = $reader->autoIncrement($legacyTable);
            if ($ai !== null) {
                DB::statement(sprintf('alter table `%s` auto_increment = %d', ColumnMap::TABLES[$legacyTable]['table'], $ai));
            }
        }

        $this->table(['legacy', 'v2', 'rows'], array_map(
            fn ($t) => [$t, ColumnMap::TABLES[$t]['table'], $counts[$t]],
            $legacyTables
        ));
        $this->info('取り込み完了。php artisan legacy:verify で検算してください。');

        return self::SUCCESS;
    }

    /**
     * @param  array{table: string, columns: array<string, string>}  $map
     * @param  array<string, mixed>  $extra  v2 で足したカラムの値
     */
    private function copyTable(LegacyReader $reader, string $legacyTable, array $map, array $extra): int
    {
        $count = 0;
        $batch = [];
        foreach ($reader->rows($legacyTable, array_keys($map['columns'])) as $row) {
            $out = [];
            foreach ($map['columns'] as $from => $to) {
                $out[$to] = $row->{$from};
            }
            $batch[] = $out + $extra;
            if (count($batch) === self::CHUNK) {
                DB::table($map['table'])->insert($batch);
                $count += count($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            DB::table($map['table'])->insert($batch);
            $count += count($batch);
        }

        return $count;
    }

    private function copySettings(LegacyReader $reader): void
    {
        foreach (ColumnMap::SETTINGS as $legacyTable => $s) {
            $value = $reader->scalar(
                sprintf('select `%s` from `%s` where id = ?', $s['column'], $legacyTable),
                [$s['id']]
            );
            if ($value !== null) {
                Setting::set($s['key'], $value);
            }
        }
    }
}
