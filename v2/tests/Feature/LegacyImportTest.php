<?php

namespace Tests\Feature;

use App\Legacy\ColumnMap;
use App\Models\Setting;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * legacy:import / legacy:verify。
 * tests/fixtures/legacy_small.sql (旧スキーマ + 架空データ) を `legacy` 接続の DB (legacy_testing) に入れて実行する。
 * import は v2 側 (testing) を migrate:fresh するので RefreshDatabase は使わず、終わったら migrate:fresh で空に戻す。
 */
class LegacyImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadLegacyFixture();
    }

    /** フィクスチャを入れてよい DB (phpunit.xml の LEGACY_DB_* / DB_DATABASE と同じ)。 */
    private const FIXTURE_DATABASE = 'legacy_testing';

    private const FIXTURE_HOST = 'db';

    private const TARGET_DATABASE = 'testing';

    /**
     * フィクスチャ (先頭で旧テーブルを DROP する) を legacy_testing に入れる。
     * 本物の旧 DB (本番を含む) を消さないよう、SQL を流す前に接続先を確かめる。
     */
    private function loadLegacyFixture(): void
    {
        $this->assertSafeTestDatabases();

        // フィクスチャの投入だけは書き込みできる接続で行う (`legacy` 接続そのものは読み取り専用)
        $fixture = config('database.connections.legacy');
        unset($fixture['options']);
        config(['database.connections.legacy_fixture' => $fixture]);
        DB::connection('legacy_fixture')->unprepared(file_get_contents(base_path('tests/fixtures/legacy_small.sql')));
        DB::purge('legacy_fixture');
    }

    /** testing 環境・設定キャッシュなし・legacy=docker の legacy_testing・既定=testing でなければ止める。 */
    private function assertSafeTestDatabases(): void
    {
        $legacy = config('database.connections.legacy');
        $target = config('database.connections.'.config('database.default'));
        $problems = array_filter([
            app()->environment('testing') ? null : 'APP_ENV が testing ではない',
            app()->configurationIsCached() ? '設定がキャッシュされている (php artisan config:clear)' : null,
            ($legacy['database'] ?? null) === self::FIXTURE_DATABASE ? null : 'legacy の DB 名が '.self::FIXTURE_DATABASE.' ではない ('.($legacy['database'] ?? '').')',
            ($legacy['host'] ?? null) === self::FIXTURE_HOST ? null : 'legacy のホストが docker の '.self::FIXTURE_HOST.' ではない ('.($legacy['host'] ?? '').')',
            ($target['database'] ?? null) === self::TARGET_DATABASE ? null : '既定の DB 名が '.self::TARGET_DATABASE.' ではない ('.($target['database'] ?? '').')',
        ]);
        if ($problems !== []) {
            throw new RuntimeException('テスト用 DB ではないのでフィクスチャを入れない: '.implode(' / ', $problems));
        }
    }

    protected function tearDown(): void
    {
        $target = config('database.connections.'.config('database.default').'.database');
        if ($target !== self::TARGET_DATABASE) {
            parent::tearDown();

            return;
        }
        // 後続のテスト (RefreshDatabase は1回しか migrate しない) に投入データを残さない
        Artisan::call('migrate:fresh', ['--force' => true]);
        parent::tearDown();
    }

    private function legacy()
    {
        return DB::connection('legacy');
    }

    public function test_import_copies_all_rows_with_same_ids(): void
    {
        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);

        foreach (ColumnMap::TABLES as $legacyTable => $map) {
            $this->assertSame(
                $this->legacy()->table($legacyTable)->orderBy('id')->pluck('id')->all(),
                DB::table($map['table'])->orderBy('id')->pluck('id')->all(),
                "ids of {$legacyTable} -> {$map['table']}"
            );
        }

        $this->assertSame([1, 2, 3, 5], DB::table('orders')->orderBy('id')->pluck('id')->all());
        $this->assertSame([1, 2, 3, 4, 6], DB::table('order_items')->orderBy('id')->pluck('id')->all());

        // 値は計算し直さない (タグ '0-000'、マイナス金額、論理削除、日時もそのまま)
        $this->assertSame('0-000', DB::table('order_items')->where('id', 3)->value('tag'));
        $this->assertSame('10-999', DB::table('order_items')->where('id', 4)->value('tag'));
        $order = DB::table('orders')->find(3);
        $this->assertSame(-300, (int) $order->amount);
        $this->assertSame('2023-01-07 00:00:00', $order->deleted_at);
        $this->assertSame('2023-01-06 10:00:00', $order->paid_at);
        $invoice = DB::table('invoices')->find(1);
        $this->assertSame('2023-01-20', $invoice->paid_at);
        $this->assertSame(2, (int) $invoice->carry_over_id);
        // 旧本番に実在する「日が 00」の日付もそのまま移す
        $this->assertSame('2023-04-00', DB::table('invoices')->where('id', 4)->value('period_end'));
        $this->assertSame('2023-11-00 00:00:00', DB::table('orders')->where('id', 5)->value('paid_at'));
        $store = DB::table('stores')->find(1);
        $this->assertSame('877-0037', $store->postal_code);
        $this->assertSame('$2y$10$abcdefghijklmnopqrstuuG1t0pZ2gq2b0ZQ1sQ1sQ1sQ1sQ1sQ1s', $store->password);
        $this->assertSame('0.10', (string) DB::table('delivery_products')->where('id', 1)->value('tax_rate'));

        // tag_numbers -> tag_counters (tag_number -> value)
        $this->assertSame(
            [1 => 3592, 2 => 1000],
            DB::table('tag_counters')->orderBy('manager_id')->pluck('value', 'manager_id')->map(fn ($v) => (int) $v)->all()
        );

        // ipaddresses (id=1) -> settings('printer_ip')
        $this->assertSame('192.168.0.214', Setting::get('printer_ip'));

        // 旧の納品書は店舗を持たないので、旧の店舗 (1件) に属させる
        $this->assertSame([1], DB::table('delivery_customers')->pluck('store_id')->map(fn ($v) => (int) $v)->unique()->values()->all());
    }

    public function test_renamed_columns_keep_values(): void
    {
        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);

        $c2 = DB::table('customers')->find(2);
        $this->assertSame(1, (int) $c2->needs_payment_confirmation);
        $this->assertSame(0, (int) $c2->needs_return_confirmation);
        $c1 = DB::table('customers')->find(1);
        $this->assertSame(0, (int) $c1->needs_payment_confirmation);
        $this->assertSame(1, (int) $c1->needs_return_confirmation);

        $columns = DB::getSchemaBuilder()->getColumnListing('customers');
        $this->assertNotContains('needs_payment_confimation', $columns);
        $this->assertNotContains('needs_return_confimation', $columns);
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('order_clothes'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('tag_numbers'));
        $this->assertFalse(DB::getSchemaBuilder()->hasTable('ipaddresses'));
    }

    public function test_import_twice_is_idempotent(): void
    {
        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);
        $first = $this->snapshot();

        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);
        $this->assertSame($first, $this->snapshot());
        $this->artisan('legacy:verify')->assertExitCode(0);
    }

    public function test_import_without_fresh_refuses_when_data_exists(): void
    {
        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);
        $this->artisan('legacy:import')->assertExitCode(1);
        $this->assertSame($this->legacy()->table('orders')->count(), DB::table('orders')->count());
    }

    public function test_verify_fails_when_counts_differ(): void
    {
        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);
        $this->artisan('legacy:verify')->assertExitCode(0);

        DB::table('order_items')->where('id', 6)->delete();

        $this->artisan('legacy:verify')->assertExitCode(1);
    }

    public function test_verify_fails_when_a_value_differs(): void
    {
        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);

        DB::table('customers')->where('id', 1)->update(['name' => '書き換え']);

        $this->artisan('legacy:verify')->assertExitCode(1);
    }

    public function test_sessions_tokens_client_errors_are_not_imported(): void
    {
        $this->assertSame(1, $this->legacy()->table('sessions')->count());
        $this->assertSame(1, $this->legacy()->table('personal_access_tokens')->count());
        $this->assertSame(1, $this->legacy()->table('client_errors')->count());

        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);

        $this->assertSame(0, DB::table('sessions')->count());
        $this->assertSame(0, DB::table('personal_access_tokens')->count());
        $this->assertSame(0, DB::table('client_errors')->count());
        $this->assertArrayNotHasKey('sessions', ColumnMap::TABLES);
        $this->assertArrayNotHasKey('personal_access_tokens', ColumnMap::TABLES);
        $this->assertArrayNotHasKey('client_errors', ColumnMap::TABLES);
    }

    public function test_only_delivery_reimports_delivery_tables_only(): void
    {
        $this->artisan('legacy:import', ['--fresh' => true])->assertExitCode(0);

        DB::table('delivery_daily_entries')->delete();
        DB::table('delivery_email_logs')->delete();
        DB::table('orders')->where('id', 1)->update(['note' => 'v2 で編集']);

        $this->artisan('legacy:import', ['--only' => 'delivery'])->assertExitCode(0);

        $this->assertSame(2, DB::table('delivery_daily_entries')->count());
        $this->assertSame(1, DB::table('delivery_email_logs')->count());
        $this->assertSame('v2 で編集', DB::table('orders')->where('id', 1)->value('note'));
    }

    public function test_import_refuses_when_target_database_is_the_legacy_database(): void
    {
        // v2 側に印を付けておき、拒否された後も残っている (migrate:fresh されていない) ことを確かめる
        Artisan::call('migrate:fresh', ['--force' => true]);
        Setting::set('marker', 'keep');

        config(['database.connections.legacy.database' => config('database.connections.'.config('database.default').'.database')]);
        DB::purge('legacy');

        $this->artisan('legacy:import', ['--fresh' => true])
            ->expectsOutputToContain('旧 DB と同じ名前')
            ->assertExitCode(1);
        $this->assertSame('keep', Setting::get('marker'));
    }

    public function test_fixture_loader_refuses_a_non_test_legacy_database(): void
    {
        config(['database.connections.legacy.database' => 'cleaningpossystem']);
        $queries = [];
        DB::listen(function (QueryExecuted $q) use (&$queries) {
            $queries[] = $q->connectionName.': '.$q->sql;
        });

        try {
            $this->loadLegacyFixture();
            $this->fail('loadLegacyFixture() must refuse a legacy database other than legacy_testing');
        } catch (RuntimeException $e) {
            // DB のエラー (QueryException も RuntimeException) ではなく、接続前のガードで止まること
            $this->assertNotInstanceOf(QueryException::class, $e);
            $this->assertStringContainsString('フィクスチャを入れない', $e->getMessage());
        }
        $this->assertSame([], $queries, 'no SQL may run before the guard refuses');
        $this->assertArrayNotHasKey('legacy_fixture', DB::getConnections(), 'no connection may be opened');
    }

    public function test_legacy_connection_is_read_only(): void
    {
        $this->expectException(QueryException::class);
        $this->legacy()->table('taxes')->insert(['id' => 99, 'store_id' => 1, 'tax' => '8']);
    }

    /** @return array<string, mixed> */
    private function snapshot(): array
    {
        $out = [];
        foreach (ColumnMap::TABLES as $map) {
            $out[$map['table']] = DB::table($map['table'])->count();
        }
        $out['orders_amount_sum'] = (int) DB::table('orders')->sum('amount');
        $out['printer_ip'] = Setting::get('printer_ip');

        return $out;
    }
}
