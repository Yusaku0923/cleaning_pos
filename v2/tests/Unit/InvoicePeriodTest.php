<?php

namespace Tests\Unit;

use App\Domain\Billing\InvoicePeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 請求期間 (旧 Utility::searchInvoicePeriod)。
 * 期待値は旧関数を実際に動かして作った tests/fixtures/invoice_periods.json
 * (作り方は tests/fixtures/make_invoice_periods.php)。
 *
 * 旧関数は 'Y-m-<締め日>' の文字列を返すので、存在しない日 (2026-02-31, 2026-07-0, 2026-01-32) も返る。
 *   - 旧が存在する日を返したとき: v2 はその日と完全に同じ。
 *   - 旧が存在しない日を返したとき (締め日 0 の日付 00、締め日 29〜31 の短い月): v2 は本当の日付にする。
 *     終了日 = その月の min(締め日, 月の日数) (締め日 0 は月末)、開始日 = 前の期間の終了日の翌日。
 *     旧 DB には 'YYYY-MM-00' / '0000-00-00' が入っていた (e2e/allowed-diffs.md)。
 * どの場合も、渡し日は必ずその期間に入る。
 */
class InvoicePeriodTest extends TestCase
{
    public static function legacyCases(): array
    {
        $json = json_decode(file_get_contents(__DIR__.'/../fixtures/invoice_periods.json'), true, flags: JSON_THROW_ON_ERROR);
        $cases = [];
        foreach ($json['cases'] as $c) {
            $cases["cutoff {$c['cutoff']} handed {$c['handed_at']}"] = [$c];
        }

        return $cases;
    }

    #[DataProvider('legacyCases')]
    public function test_matches_legacy_search_invoice_period(array $c): void
    {
        $handed = CarbonImmutable::parse($c['handed_at']);
        [$start, $end] = InvoicePeriod::for($c['cutoff'], $handed);

        $this->assertSame('00:00:00', $start->format('H:i:s'));
        $this->assertTrue($start->lte($handed) && $end->gte($handed), "handed {$c['handed_at']} must be inside {$start->toDateString()}..{$end->toDateString()}");

        foreach (['start' => $start, 'end' => $end] as $key => $v2) {
            $raw = $c["{$key}_raw"];
            if ($this->isRealDate($raw)) {
                // 旧が存在する日を返したときは完全に同じ
                $this->assertSame(CarbonImmutable::parse($c[$key])->toDateString(), $v2->toDateString(), "{$key} (legacy {$raw})");
                $this->assertSame($c[$key], $this->normalize($raw));
            }
        }

        // 存在しない日の代わり: 終了日は min(締め日, 月の日数)、締め日 0 / 99 は月末
        $endDay = in_array($c['cutoff'], [0, 99], true) ? $end->daysInMonth : min($c['cutoff'], $end->daysInMonth);
        $this->assertSame($endDay, $end->day, 'end day');
        // 開始日は前の期間の終了日の翌日
        [, $prevEnd] = InvoicePeriod::for($c['cutoff'], $start->subDay());
        $this->assertSame($prevEnd->addDay()->toDateString(), $start->toDateString(), 'start = previous end + 1');
    }

    private function isRealDate(string $raw): bool
    {
        [$y, $m, $d] = array_map('intval', explode('-', $raw));

        return checkdate($m, $d, $y);
    }

    private function normalize(string $raw): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', $raw));

        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    /** 旧が存在しない日を返す締め日 29〜31 の例 (レビュー指摘: 渡し日が期間外になっていた)。 */
    public function test_short_month_cutoffs_are_clamped(): void
    {
        $cases = [
            // [締め日, 渡し日, 開始, 終了]
            [30, '2026-03-01', '2026-03-01', '2026-03-30'],  // 旧: 2026-02-31 .. 2026-03-30
            [31, '2026-02-10', '2026-02-01', '2026-02-28'],  // 旧: 2026-01-32 .. 2026-02-31
            [29, '2026-02-28', '2026-01-30', '2026-02-28'],  // 旧: 2026-01-30 .. 2026-02-29
            [29, '2026-03-01', '2026-03-01', '2026-03-29'],  // 旧: 2026-02-30 .. 2026-03-29
            [31, '2026-04-30', '2026-04-01', '2026-04-30'],  // 旧: 2026-03-32 .. 2026-04-31
            [29, '2024-02-29', '2024-01-30', '2024-02-29'],  // うるう年は旧も正しい
        ];
        foreach ($cases as [$cutoff, $handed, $s, $e]) {
            [$start, $end] = InvoicePeriod::for($cutoff, CarbonImmutable::parse($handed));
            $this->assertSame([$s, $e], [$start->toDateString(), $end->toDateString()], "cutoff {$cutoff} handed {$handed}");
        }
    }

    public function test_fixture_covers_the_brief_matrix(): void
    {
        $seen = [];
        foreach (self::legacyCases() as [$c]) {
            $seen[$c['cutoff'].'|'.$c['handed_at']] = true;
        }
        foreach ([5, 20, 25, 28, 29, 30, 31, 99] as $cutoff) {
            foreach (['2026-01-15', '2026-02-10', '2026-02-28', '2026-03-31', '2026-12-31'] as $d) {
                $this->assertArrayHasKey("{$cutoff}|{$d}", $seen);
            }
        }
    }

    /** 本番の請求書 1281 / 1289 (締め日 0 のまま請求した顧客): 旧は period_end が 2026-07-00 / 2026-08-00。 */
    public function test_cutoff_zero_returns_real_month_end_instead_of_day_00(): void
    {
        [$start, $end] = InvoicePeriod::for(0, CarbonImmutable::parse('2026-06-23 15:59:24'));
        $this->assertSame(['2026-06-01', '2026-06-30'], [$start->toDateString(), $end->toDateString()]);

        [$start, $end] = InvoicePeriod::for(0, CarbonImmutable::parse('2026-07-01 16:15:15'));
        $this->assertSame(['2026-07-01', '2026-07-31'], [$start->toDateString(), $end->toDateString()]);

        [, $end] = InvoicePeriod::for(0, CarbonImmutable::parse('2024-02-10'));
        $this->assertSame('2024-02-29', $end->toDateString());
    }

    public function test_consecutive_periods_neither_gap_nor_overlap(): void
    {
        foreach ([0, 1, 5, 15, 20, 25, 28, 29, 30, 31, 99] as $cutoff) {
            $day = CarbonImmutable::parse('2025-12-01');
            [, $prevEnd] = InvoicePeriod::for($cutoff, $day);
            for ($i = 0; $i < 400; $i++) {
                $day = $day->addDay();
                [$start, $end] = InvoicePeriod::for($cutoff, $day);
                $this->assertTrue($start->lte($day) && $end->gte($day), "cutoff {$cutoff}: {$day->toDateString()} is in its period");
                if (! $end->equalTo($prevEnd)) {
                    $this->assertSame($prevEnd->addDay()->toDateString(), $start->toDateString(), "cutoff {$cutoff} at {$day->toDateString()}");
                    $prevEnd = $end;
                }
            }
        }
    }

    public function test_month_end_cutoff(): void
    {
        [$start, $end] = InvoicePeriod::for(InvoicePeriod::MONTH_END_CUTOFF, CarbonImmutable::parse('2024-02-10 12:00'));
        $this->assertSame(['2024-02-01', '2024-02-29'], [$start->toDateString(), $end->toDateString()]);
    }

    public function test_cutoff_day_itself_belongs_to_the_period_ending_that_day(): void
    {
        // 旧: 締め日 < 渡し日 のときだけ翌期間。締め日当日は今期間
        [$start, $end] = InvoicePeriod::for(20, CarbonImmutable::parse('2026-05-20'));
        $this->assertSame(['2026-04-21', '2026-05-20'], [$start->toDateString(), $end->toDateString()]);
        [$start, $end] = InvoicePeriod::for(20, CarbonImmutable::parse('2026-05-21'));
        $this->assertSame(['2026-05-21', '2026-06-20'], [$start->toDateString(), $end->toDateString()]);
    }

    public function test_defaults_to_today(): void
    {
        CarbonImmutable::setTestNow('2026-10-23 10:00:00');
        try {
            [$start, $end] = InvoicePeriod::for(99);
            $this->assertSame(['2026-10-01', '2026-10-31'], [$start->toDateString(), $end->toDateString()]);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
