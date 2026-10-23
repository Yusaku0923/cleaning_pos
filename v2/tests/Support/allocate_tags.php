<?php

/*
 * TagAllocatorTest 用: 別プロセス (= 別の DB 接続) で TagAllocator::allocate を 1 回呼び、
 * 振られた番号を JSON で出す。使い方: php allocate_tags.php <managerId> <count>
 * DB はテスト側が環境変数で testing を指定して起動する。
 */

use App\Domain\Tag\TagAllocator;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.connections.mysql.database') !== 'testing') {
    fwrite(STDERR, "refusing: not the testing database\n");
    exit(2);
}

$allocator = $app->make(TagAllocator::class);
DB::connection()->getPdo(); // 接続まで済ませてから合図する
fwrite(STDERR, "ready\n");
fflush(STDERR);

$tags = $allocator->allocate((int) $argv[1], (int) $argv[2]);

echo json_encode(array_map(fn ($t) => $t->toInt(), $tags));
