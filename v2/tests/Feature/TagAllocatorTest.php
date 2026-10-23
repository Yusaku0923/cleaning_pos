<?php

namespace Tests\Feature;

use App\Domain\Tag\TagAllocator;
use App\Domain\Tag\TagCounterUnavailable;
use App\Domain\Tag\TagNumber;
use App\Models\Store;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * タグ採番 (旧 OrdersController::store / addTag)。
 * tag_counters.value は旧 tag_numbers.tag_number と同じく「次に振る番号」。
 * 同時に 2 つの注文が来ても番号が重ならないこと (lockForUpdate) を、本物の 2 接続で確かめる。
 * 別接続から行が見えるように、トランザクションで包む RefreshDatabase ではなく DatabaseMigrations を使う。
 */
class TagAllocatorTest extends TestCase
{
    use DatabaseMigrations;

    private function seedManager(?int $counter): int
    {
        $t = ['created_at' => now(), 'updated_at' => now()];
        $store = DB::table('stores')->insertGetId(['name' => '店', 'email' => 's@example.com', 'password' => 'x', 'address' => '住所', 'phone_number' => '000'] + $t);
        $manager = DB::table('managers')->insertGetId(['store_id' => $store, 'name' => '担当'] + $t);
        if ($counter !== null) {
            DB::table('tag_counters')->insert(['manager_id' => $manager, 'value' => $counter] + $t);
        }

        return $manager;
    }

    private function ints(array $tags): array
    {
        return array_map(fn (TagNumber $t) => $t->toInt(), $tags);
    }

    public function test_allocates_from_next_value_and_saves_the_following_one(): void
    {
        $manager = $this->seedManager(3592);

        $tags = app(TagAllocator::class)->allocate($manager, 3);

        $this->assertSame([3592, 3593, 3594], $this->ints($tags));
        $this->assertSame('3-592', $tags[0]->toString());
        $this->assertSame(3595, (int) DB::table('tag_counters')->where('manager_id', $manager)->value('value'));
    }

    public function test_wraps_after_10999(): void
    {
        $manager = $this->seedManager(10998);

        $this->assertSame([10998, 10999, 1000], $this->ints(app(TagAllocator::class)->allocate($manager, 3)));
        $this->assertSame(1001, (int) DB::table('tag_counters')->where('manager_id', $manager)->value('value'));
    }

    public function test_missing_counter_starts_at_1000(): void
    {
        $manager = $this->seedManager(null);

        $this->assertSame([1000, 1001], $this->ints(app(TagAllocator::class)->allocate($manager, 2)));
        $this->assertSame(1002, (int) DB::table('tag_counters')->where('manager_id', $manager)->value('value'));
    }

    public function test_counter_hidden_by_store_scope_throws_domain_exception(): void
    {
        $other = $this->seedManager(1500); // 店 s@example.com の担当者
        $mine = Store::create(['name' => '自店', 'email' => 'me@example.com', 'password' => 'x', 'address' => '住所', 'phone_number' => '000']);
        $this->actingAs($mine);

        try {
            app(TagAllocator::class)->allocate($other, 1);
            $this->fail('expected TagCounterUnavailable');
        } catch (TagCounterUnavailable $e) {
            $this->assertStringContainsString((string) $other, $e->getMessage());
        }
        $this->assertSame(1500, (int) DB::table('tag_counters')->where('manager_id', $other)->value('value'));
    }

    public function test_zero_count_allocates_nothing(): void
    {
        $manager = $this->seedManager(1500);

        $this->assertSame([], app(TagAllocator::class)->allocate($manager, 0));
        $this->assertSame(1500, (int) DB::table('tag_counters')->where('manager_id', $manager)->value('value'));
    }

    public function test_two_parallel_allocations_do_not_overlap(): void
    {
        $manager = $this->seedManager(10997);

        // 接続 1 (このプロセス): トランザクションの中で 3 件振り、まだ確定しない
        DB::beginTransaction();
        $first = $this->ints(app(TagAllocator::class)->allocate($manager, 3));

        // 接続 2 (別プロセス): 同じ担当者に 3 件。接続 1 のロックが外れるまで待つはず
        $env = array_merge(getenv(), [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => 'testing',
        ]);
        $proc = proc_open(
            [PHP_BINARY, base_path('tests/Support/allocate_tags.php'), (string) $manager, '3'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes, base_path(), $env,
        );
        $this->assertIsResource($proc);

        // 子が起動して接続し終わる (stderr に ready) まで待ってから、さらに 1 秒置く
        $ready = fgets($pipes[2]);
        $this->assertSame("ready\n", $ready, 'child did not start');
        usleep(1_000_000);
        $this->assertTrue(proc_get_status($proc)['running'], 'second connection must wait for the row lock');

        DB::commit();

        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        $code = proc_close($proc);
        $this->assertSame(0, $code, "child failed: {$err}");
        $second = json_decode($out, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([10997, 10998, 10999], $first);
        $this->assertSame([1000, 1001, 1002], $second);
        $all = array_merge($first, $second);
        $this->assertCount(6, array_unique($all));
        $this->assertSame(1003, (int) DB::table('tag_counters')->where('manager_id', $manager)->value('value'));
    }
}
