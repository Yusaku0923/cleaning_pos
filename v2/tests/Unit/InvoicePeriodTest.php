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
 * v2 は旧の PHP がその文字列を日付として読んだ値 (strtotime。旧の繰越処理もこれで読む) と同じにする。
 *   - 2026-02-31 → 2026-03-03 のように翌月に繰り上がる (旧と同じ)
 *   - 日付 00 (締め日 0 = 未選択) → 前月末 = 本当の月末 (旧 DB には 'YYYY-MM-00' が入る。e2e/allowed-diffs.md)
 *   - 32 日 (締め日 31 の開始日) → 旧の strtotime は読めず 1970-01-01。v2 は 1 日 + 31 日 (e2e/allowed-diffs.md)
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
        [$start, $end] = InvoicePeriod::for($c['cutoff'], CarbonImmutable::parse($c['handed_at']));

        $this->assertSame($this->expected($c['start_raw'], $c['start']), $start->toDateString(), 'start');
        $this->assertSame($this->expected($c['end_raw'], $c['end']), $end->toDateString(), 'end');
        $this->assertSame('00:00:00', $start->format('H:i:s'));
    }

    /**
     * 旧の値。ただし旧の PHP が読めなかった 32 日だけは、他の日と同じ繰り上がりで「その月の 1 日 + 31 日」。
     * こうすると前の期間の終了日 (例: 2026-02-31 → 03-03) の翌日 (03-04) から始まり、期間が途切れも重なりもしない。
     */
    private function expected(string $raw, string $legacyRead): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', $raw));
        if ($legacyRead === '1970-01-01') {
            $this->assertSame(32, $d, "only day 32 is unreadable by legacy: {$raw}");

            return CarbonImmutable::create($y, $m, 1)->addDays(31)->toDateString();
        }
        if ($d === 0) {
            // 旧の strtotime も前月末として読む (DB には 00 のまま入る)
            $this->assertSame(CarbonImmutable::create($y, $m, 1)->subDay()->toDateString(), $legacyRead);
        }

        return $legacyRead;
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
                if ($cutoff <= 28 || $cutoff === 99) {
                    // 29〜31 は旧と同じく、短い月の直後の数日がその日を含まない期間に入る (画面では選べない締め日)
                    $this->assertTrue($start->lte($day) && $end->gte($day), "cutoff {$cutoff}: {$day->toDateString()} is in its period");
                }
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
