<?php declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\LocalDateTimeDelta;
use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Period;
use PHPUnit\Framework\TestCase;

class LocalDateTimeDeltaTest extends TestCase
{
    public function testCanBeCreatedFromPeriodAndDuration(): void
    {
        $period = new Period(years: 1, months: 2, days: 3);
        $duration = new Duration(hours: 4, minutes: 5, seconds: 6);
        $delta = LocalDateTimeDelta::of($period, $duration);

        self::assertSame($period, $delta->period);
        self::assertSame($duration, $delta->duration);
    }

    public function testParseFullIsoString(): void
    {
        $delta = LocalDateTimeDelta::parse('P1Y2M3DT4H5M6.789S');

        self::assertSame(1, $delta->period->years);
        self::assertSame(2, $delta->period->months);
        self::assertSame(3, $delta->period->days);
        self::assertSame(4, $delta->duration->hours);
        self::assertSame(5, $delta->duration->minutes);
        self::assertSame(6, $delta->duration->seconds);
        self::assertSame(789_000_000, $delta->duration->nanos);
    }

    public function testParsePeriodOnly(): void
    {
        $delta = LocalDateTimeDelta::parse('P1Y');
        
        self::assertSame(1, $delta->period->years);
        self::assertSame(0, $delta->duration->hours);
    }

    public function testParseDurationOnly(): void
    {
        $delta = LocalDateTimeDelta::parse('PT1H');
        
        self::assertSame(0, $delta->period->years);
        self::assertSame(1, $delta->duration->hours);
    }

    public function testToString(): void
    {
        $delta = LocalDateTimeDelta::parse('P1Y2M3DT4H5M6.789S');
        self::assertSame('P1Y2M3DT4H5M6.789S', (string) $delta);
    }

    public function testToStringPeriodOnly(): void
    {
        $delta = LocalDateTimeDelta::parse('P1Y');
        self::assertSame('P1Y', (string) $delta);
    }

    public function testToStringDurationOnly(): void
    {
        $delta = LocalDateTimeDelta::parse('PT1H');
        self::assertSame('PT1H', (string) $delta);
    }

    public function testPlus(): void
    {
        $d1 = LocalDateTimeDelta::parse('P1YT1H');
        $d2 = LocalDateTimeDelta::parse('P1MT1M');
        $result = $d1->plus($d2);

        self::assertSame('P1Y1MT1H1M', (string) $result);
    }

    public function testMinus(): void
    {
        $d1 = LocalDateTimeDelta::parse('P2Y2M2DT2H2M2S');
        $d2 = LocalDateTimeDelta::parse('P1Y1M1DT1H1M1S');
        $result = $d1->minus($d2);

        self::assertSame('P1Y1M1DT1H1M1S', (string) $result);
    }
}
