<?php

namespace App\Domain\Tag;

use App\Models\TagCounter;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * 担当者ごとのタグ採番 (旧 OrdersController::store)。
 * tag_counters.value は旧 tag_numbers.tag_number と同じく「次に振る番号」(移行した値をそのまま使える)。
 * value から順に $count 件振り、最後に振った番号の次を value に保存する。
 * 旧はロックなしで読み書きしていたので同時注文で番号が重なりえた。v2 は行ロックで直列にする。
 */
class TagAllocator
{
    /** @return TagNumber[] */
    public function allocate(int $managerId, int $count): array
    {
        if ($count < 0) {
            throw new InvalidArgumentException("count must be >= 0: {$count}");
        }

        return DB::transaction(function () use ($managerId, $count) {
            $counter = $this->lockCounter($managerId);

            $tag = TagNumber::fromInt((int) $counter->value);
            $tags = [];
            for ($i = 0; $i < $count; $i++) {
                $tags[] = $tag;
                $tag = $tag->next();
            }

            if ($count > 0) {
                $counter->value = $tag->toInt();
                $counter->save();
            }

            return $tags;
        });
    }

    private function lockCounter(int $managerId): TagCounter
    {
        $find = fn () => TagCounter::withTrashed()->where('manager_id', $managerId)->lockForUpdate()->first();

        $counter = $find();
        if ($counter === null) {
            // 行がない担当者は 1000 から。manager_id は unique なので同時に作られても 1 行
            TagCounter::query()->insertOrIgnore([
                'manager_id' => $managerId, 'value' => TagNumber::MIN,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $counter = $find();
        }

        return $counter;
    }
}
