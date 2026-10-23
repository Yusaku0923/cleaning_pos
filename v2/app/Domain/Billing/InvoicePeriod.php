<?php

namespace App\Domain\Billing;

use Carbon\CarbonImmutable;

/**
 * 請求期間 (旧 Utility::searchInvoicePeriod)。
 *
 * 旧は date('Y-m-<締め日>') で文字列を作るので、存在しない日 ('2026-02-31', '2026-07-0', '2026-01-32') も返した
 * (旧 DB には 'YYYY-MM-00' / '0000-00-00' が入った)。
 * v2 は、旧が存在する日を返す場合はそれと完全に同じ日を返し、存在しない日の場合は本当の日付にする:
 *   - 期間の終了日 = その月の min(締め日, 月の日数)。締め日 99 (末日) と 0 (未選択。旧は '翌月-00' = 月末) は月末
 *   - 期間の開始日 = 前の期間の終了日の翌日
 * こうすると渡し日は必ずその期間に入り、期間は途切れも重なりもしない (e2e/allowed-diffs.md)。
 * 締め日 1〜28 と 99 では旧と全く同じ。違うのは 0 と、画面で選べない 29〜31 の短い月だけ。
 */
final class InvoicePeriod
{
    public const MONTH_END_CUTOFF = 99;

    /** 締め日 0 = 顧客登録の「日付を選択してください」。旧の結果は月末締めと同じ期間 (終了日だけ '-00')。 */
    public const UNSET_CUTOFF = 0;

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} [期間の開始日, 終了日] (どちらも 00:00:00) */
    public static function for(int $cutoffDay, ?CarbonImmutable $handedAt = null): array
    {
        $handedAt ??= CarbonImmutable::now();
        $month = $handedAt->startOfMonth()->startOfDay();

        // 旧: 締め日 < 渡し日の「日」なら翌月締め、そうでなければ当月締め (締め日 0 は常に翌月 '-00' = 当月末)
        $end = self::periodEnd($month, $cutoffDay);
        if ($handedAt->startOfDay()->gt($end)) {
            $end = self::periodEnd($month->addMonthNoOverflow(), $cutoffDay);
        }
        $start = self::periodEnd($end->startOfMonth()->subMonthNoOverflow(), $cutoffDay)->addDay();

        return [$start, $end];
    }

    /** $firstOfMonth の月の締め日 (本当に存在する日)。 */
    public static function periodEnd(CarbonImmutable $firstOfMonth, int $cutoffDay): CarbonImmutable
    {
        $first = $firstOfMonth->startOfMonth()->startOfDay();
        if ($cutoffDay === self::MONTH_END_CUTOFF || $cutoffDay === self::UNSET_CUTOFF) {
            return $first->endOfMonth()->startOfDay();
        }

        return $first->addDays(min($cutoffDay, $first->daysInMonth) - 1);
    }
}
