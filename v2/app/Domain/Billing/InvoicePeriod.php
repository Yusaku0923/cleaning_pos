<?php

namespace App\Domain\Billing;

use Carbon\CarbonImmutable;

/**
 * 請求期間 (旧 Utility::searchInvoicePeriod)。
 *
 * 旧は date('Y-m-<締め日>') で文字列を作るので、存在しない日 ('2026-02-31', '2026-07-0', '2026-01-32') も返した。
 * v2 は「その月の 1 日 + (日 - 1) 日」で日付にする。これは旧の PHP (strtotime) がその文字列を読んだ値と同じ:
 *   - 2026-02-31 → 2026-03-03 (翌月に繰り上がる。旧と同じ)
 *   - 日 0 → 前月末。締め日 0 (顧客登録の「日付を選択してください」) の顧客で旧 DB には 'YYYY-MM-00' が入った。
 *     v2 は本当の月末を入れる (e2e/allowed-diffs.md)
 *   - 日 32 (締め日 31 の開始日) → 1 日 + 31 日 = 前の期間の終了日の翌日 (旧は読めず 1970-01-01 / DB 0000-00-00。
 *     e2e/allowed-diffs.md)
 * 締め日 29〜31 は顧客画面で選べない (1〜28 と末日) ので本番には無い。
 */
final class InvoicePeriod
{
    public const MONTH_END_CUTOFF = 99;

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} [期間の開始日, 終了日] (どちらも 00:00:00) */
    public static function for(int $cutoffDay, ?CarbonImmutable $handedAt = null): array
    {
        $handedAt ??= CarbonImmutable::now();
        $month = $handedAt->startOfMonth()->startOfDay();

        if ($cutoffDay === self::MONTH_END_CUTOFF) {
            return [$month, $month->endOfMonth()->startOfDay()];
        }

        if ($cutoffDay < $handedAt->day) {
            return [
                self::day($month, $cutoffDay + 1),
                self::day($month->addMonthNoOverflow(), $cutoffDay),
            ];
        }

        return [
            self::day($month->subMonthNoOverflow(), $cutoffDay + 1),
            self::day($month, $cutoffDay),
        ];
    }

    /** 旧の date('Y-m-'.$day) を日付として読んだ値 (月の 1 日から $day - 1 日進める)。 */
    private static function day(CarbonImmutable $firstOfMonth, int $day): CarbonImmutable
    {
        return $firstOfMonth->addDays($day - 1);
    }
}
