<?php

declare(strict_types=1);

namespace Tests\Timezone;

use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\Timezone\ZoneRulesProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for ZoneRules POSIX DST parser.
 *
 * Covers:
 * - weekSpec 5 (last occurrence) — Europe/Warsaw CET-1CEST,M3.5.0/2,M10.5.0/3
 * - weekSpec 2 (second occurrence) — America/New_York EST5EDT,M3.2.0,M11.1.0
 * - weekSpec 1 (first occurrence) — America/New_York November rule
 */
class ZoneRulesTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Europe/Warsaw  CET-1CEST,M3.5.0/2,M10.5.0/3  (weekSpec 5 — last Sun)
    // -----------------------------------------------------------------------

    /**
     * @return array<string, array{int, int}>
     */
    public static function europeWarsawOffsets(): array
    {
        return [
            // Winter (CET = UTC+1)
            'Warsaw winter 2024-01-15 UTC' => [mktime(12, 0, 0, 1, 15, 2024), 3600],
            // After spring forward: 2024-03-31 02:00 local → 03:00 (last Sunday of March)
            // UTC timestamp 2024-03-31 01:00:00 UTC is in CEST territory
            'Warsaw summer 2024-04-01 UTC' => [mktime(12, 0, 0, 4, 1, 2024), 7200],
            // After fall back: 2024-10-27 03:00 local → 02:00 (last Sunday of October)
            // UTC timestamp 2024-10-28 UTC is in CET territory
            'Warsaw winter 2024-10-28 UTC' => [mktime(12, 0, 0, 10, 28, 2024), 3600],
        ];
    }

    #[DataProvider('europeWarsawOffsets')]
    public function testEuropeWarsawOffset(int $utcTimestamp, int $expectedOffsetSeconds): void
    {
        $rules = ZoneRulesProvider::getRules('Europe/Warsaw');
        $instant = Instant::ofEpochSecond($utcTimestamp);
        $offset = $rules->getOffset($instant);

        self::assertSame(
            $expectedOffsetSeconds,
            $offset->totalSeconds,
            sprintf(
                'Europe/Warsaw offset at %s (UTC %d) should be %+d s',
                date('Y-m-d H:i:s', $utcTimestamp),
                $utcTimestamp,
                $expectedOffsetSeconds,
            ),
        );
    }

    // -----------------------------------------------------------------------
    // America/New_York  EST5EDT,M3.2.0,M11.1.0
    // weekSpec=2  → 2nd Sunday of March  (regression: was returning March 1)
    // weekSpec=1  → 1st Sunday of November
    // -----------------------------------------------------------------------

    /**
     * @return array<string, array{int, int}>
     */
    public static function americaNewYorkOffsets(): array
    {
        return [
            // Winter (EST = UTC-5)
            'NY winter 2024-01-15 UTC' => [mktime(12, 0, 0, 1, 15, 2024), -18000],
            // Before DST start: 2024-03-10 06:59:59 UTC = 01:59:59 EST (still -5)
            'NY before spring 2024-03-10 06:59 UTC' => [mktime(6, 59, 59, 3, 10, 2024), -18000],
            // After DST start: 2024-03-10 07:00:00 UTC = 03:00:00 EDT (now -4)
            // 2nd Sunday of March 2024 = March 10.  02:00 local = 07:00 UTC.
            'NY after spring 2024-03-10 07:00 UTC' => [mktime(7, 0, 0, 3, 10, 2024), -14400],
            // Summer (EDT = UTC-4)
            'NY summer 2024-07-04 UTC' => [mktime(12, 0, 0, 7, 4, 2024), -14400],
            // Before DST end: 2024-11-03 05:59:59 UTC = 01:59:59 EDT (still -4)
            // 1st Sunday of November 2024 = November 3. 02:00 local = 06:00 UTC.
            'NY before fall 2024-11-03 05:59 UTC' => [mktime(5, 59, 59, 11, 3, 2024), -14400],
            // After DST end: 2024-11-03 06:00:00 UTC = 01:00:00 EST (now -5)
            'NY after fall 2024-11-03 06:00 UTC' => [mktime(6, 0, 0, 11, 3, 2024), -18000],
            // Deep winter (EST = UTC-5)
            'NY winter 2024-12-25 UTC' => [mktime(12, 0, 0, 12, 25, 2024), -18000],
        ];
    }

    #[DataProvider('americaNewYorkOffsets')]
    public function testAmericaNewYorkOffset(int $utcTimestamp, int $expectedOffsetSeconds): void
    {
        $rules = ZoneRulesProvider::getRules('America/New_York');
        $instant = Instant::ofEpochSecond($utcTimestamp);
        $offset = $rules->getOffset($instant);

        self::assertSame(
            $expectedOffsetSeconds,
            $offset->totalSeconds,
            sprintf(
                'America/New_York offset at %s (UTC %d) should be %+d s',
                date('Y-m-d H:i:s', $utcTimestamp),
                $utcTimestamp,
                $expectedOffsetSeconds,
            ),
        );
    }

    /**
     * Verify 2nd Sunday of March 2024 is March 10 (not March 1 — the old bug)
     */
    public function testSpringForward2024NewYorkIsOnMarch10NotMarch1(): void
    {
        $rules = ZoneRulesProvider::getRules('America/New_York');

        // March 1 2024 12:00 UTC has EST (-5 h) — not yet DST
        $march1Instant = Instant::ofEpochSecond(mktime(12, 0, 0, 3, 1, 2024));
        self::assertSame(-18000, $rules->getOffset($march1Instant)->totalSeconds, 'March 1 should still be EST (-5)');

        // March 10 2024 07:30 UTC has EDT (-4 h)  — past the 07:00 UTC crossover
        $march10Instant = Instant::ofEpochSecond(mktime(7, 30, 0, 3, 10, 2024));
        self::assertSame(-14400, $rules->getOffset($march10Instant)->totalSeconds, 'March 10 07:30 UTC should be EDT (-4)');
    }

    /**
     * Future year smoke test (POSIX path, not historical tzif transitions)
     */
    public function testFutureYearNewYorkUsesEdtInSummer(): void
    {
        $rules = ZoneRulesProvider::getRules('America/New_York');

        // 2030-07-04 is well in the future — POSIX rules must apply
        $summerInstant = Instant::ofEpochSecond(mktime(12, 0, 0, 7, 4, 2030));
        $offset = $rules->getOffset($summerInstant);

        self::assertSame(-14400, $offset->totalSeconds, '2030 NY summer should be EDT (-4 h)');
    }
}
