<?php

namespace App\Legacy;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use RuntimeException;

/**
 * 旧 DB を読むための唯一の入口。発行するのは SELECT だけ (書き込み・DDL のメソッドを持たない)。
 * 加えて接続自体が READ ONLY セッション (config/database.php の legacy)。
 */
final class LegacyReader
{
    public const CONNECTION = 'legacy';

    private ConnectionInterface $db;

    public function __construct()
    {
        $this->db = DB::connection(self::CONNECTION);
    }

    public static function databaseName(): string
    {
        return (string) config('database.connections.'.self::CONNECTION.'.database');
    }

    /** @return list<string> */
    public function tables(): array
    {
        return array_map(fn ($r) => $r->name, $this->select(
            "select table_name as name from information_schema.tables where table_schema = ? and table_type = 'BASE TABLE' order by table_name",
            [self::databaseName()]
        ));
    }

    /** @return list<string> */
    public function columns(string $table): array
    {
        return array_map(fn ($r) => $r->name, $this->select(
            'select column_name as name from information_schema.columns where table_schema = ? and table_name = ? order by ordinal_position',
            [self::databaseName(), $table]
        ));
    }

    public function autoIncrement(string $table): ?int
    {
        $rows = $this->select(
            'select auto_increment as ai from information_schema.tables where table_schema = ? and table_name = ?',
            [self::databaseName(), $table]
        );

        return isset($rows[0]) && $rows[0]->ai !== null ? (int) $rows[0]->ai : null;
    }

    /**
     * 全行を id 順に 1,000 行ずつ読む (where id > ? order by id limit 1000)。
     *
     * @param  list<string>  $columns
     */
    public function rows(string $table, array $columns): LazyCollection
    {
        return $this->db->table($table)->select($columns)->lazyById(1000, 'id');
    }

    /** SELECT 文だけを通す。 */
    public function select(string $sql, array $bindings = []): array
    {
        if (! preg_match('/^\s*select\s/i', $sql)) {
            throw new RuntimeException('LegacyReader は SELECT しか実行しない: '.$sql);
        }

        return $this->db->select($sql, $bindings);
    }

    public function scalar(string $sql, array $bindings = []): mixed
    {
        $row = $this->select($sql, $bindings)[0] ?? null;

        return $row === null ? null : array_values((array) $row)[0];
    }
}
