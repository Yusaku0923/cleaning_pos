<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 旧スキーマのフィクスチャを `legacy` 接続の DB (legacy_testing) に入れる (legacy:import を動かすテスト用)。
 * import は v2 側 (testing) を migrate:fresh するので RefreshDatabase は使わず、終わったら migrate:fresh で空に戻す。
 */
trait LegacyFixtureDatabase
{
    /** フィクスチャを入れてよい DB (phpunit.xml の LEGACY_DB_* / DB_DATABASE と同じ)。 */
    private static string $fixtureDatabase = 'legacy_testing';

    private static string $fixtureHost = 'db';

    private static string $targetDatabase = 'testing';

    /**
     * フィクスチャ (先頭で旧テーブルを DROP する) を legacy_testing に入れる。$then があれば、同じ書き込み用の接続で続けて呼ぶ。
     * 本物の旧 DB (本番を含む) を消さないよう、SQL を流す前に接続先を確かめる。
     *
     * @param  (callable(Connection): void)|null  $then
     */
    protected function loadLegacyFixture(?callable $then = null): void
    {
        $this->assertSafeTestDatabases();

        // フィクスチャの投入だけは書き込みできる接続で行う (`legacy` 接続そのものは読み取り専用)
        $fixture = config('database.connections.legacy');
        unset($fixture['options']);
        config(['database.connections.legacy_fixture' => $fixture]);
        try {
            $conn = DB::connection('legacy_fixture');
            $conn->unprepared(file_get_contents(base_path('tests/fixtures/legacy_small.sql')));
            if ($then !== null) {
                $then($conn);
            }
        } finally {
            DB::purge('legacy_fixture');
        }
    }

    /** testing 環境・設定キャッシュなし・legacy=docker の legacy_testing・既定=testing でなければ止める。 */
    protected function assertSafeTestDatabases(): void
    {
        $legacy = config('database.connections.legacy');
        $target = config('database.connections.'.config('database.default'));
        $problems = array_filter([
            app()->environment('testing') ? null : 'APP_ENV が testing ではない',
            app()->configurationIsCached() ? '設定がキャッシュされている (php artisan config:clear)' : null,
            ($legacy['database'] ?? null) === self::$fixtureDatabase ? null : 'legacy の DB 名が '.self::$fixtureDatabase.' ではない ('.($legacy['database'] ?? '').')',
            ($legacy['host'] ?? null) === self::$fixtureHost ? null : 'legacy のホストが docker の '.self::$fixtureHost.' ではない ('.($legacy['host'] ?? '').')',
            ($target['database'] ?? null) === self::$targetDatabase ? null : '既定の DB 名が '.self::$targetDatabase.' ではない ('.($target['database'] ?? '').')',
        ]);
        if ($problems !== []) {
            throw new RuntimeException('テスト用 DB ではないのでフィクスチャを入れない: '.implode(' / ', $problems));
        }
    }

    /** 後続のテスト (RefreshDatabase は1回しか migrate しない) に投入データを残さない。tearDown から呼ぶ。 */
    protected function resetImportedDatabase(): void
    {
        $target = config('database.connections.'.config('database.default').'.database');
        if ($target === self::$targetDatabase) {
            Artisan::call('migrate:fresh', ['--force' => true]);
        }
    }
}
