<?php declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\InvalidDuration;
use PHPUnit\Framework\TestCase;
use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\DurationFormat;

final class DurationTest extends TestCase
{
    public function testCanBeCreatedFromNamedArguments(): void
    {
        $duration = new Duration(years: 1, months: 2, days: 3, hours: 4, minutes: 5, seconds: 6, nanos: 7);

        self::assertSame(1, $duration->years);
        self::assertSame(2, $duration->months);
        self::assertSame(3, $duration->days);
        self::assertSame(4, $duration->hours);
        self::assertSame(5, $duration->minutes);
        self::assertSame(6, $duration->seconds);
        self::assertSame(7, $duration->nanos);
    }

    public function testParsingStandardFormat(): void
    {
        $duration = Duration::parse('P1Y2M3DT4H5M6.789S');
        self::assertEquals(new Duration(1, 2, 3, 4, 5, 6, 789_000_000), $duration);
    }

    public function testParsingExtendedFormat(): void
    {
        $duration = Duration::parse('P0001-02-03T04:05:06', DurationFormat::IsoExtended);
        self::assertEquals(new Duration(1, 2, 3, 4, 5, 6), $duration);
    }

    public function testFormattingStandardFormat(): void
    {
        $duration = new Duration(1, 2, 3, 4, 5, 6, 789_000_000);
        self::assertSame('P1Y2M3DT4H5M6.789S', $duration->format());
    }

    public function testFormattingExtendedFormat(): void
    {
        $duration = new Duration(1, 2, 3, 4, 5, 6);
        self::assertSame('P0001-02-03T04:05:06', $duration->format(DurationFormat::IsoExtended));
    }

    public function testToStringDefaultsToStandardFormat(): void
    {
        $duration = new Duration(1, 0, 0);
        self::assertSame('P1Y', (string) $duration);
    }

    public function testPlusMethod(): void
    {
        $d = new Duration(1, 2, 3)->plus(days: 5, hours: 10);
        self::assertEquals(new Duration(1, 2, 8, 10), $d);
    }

    public function testMinusMethod(): void
    {
        $d = new Duration(5, 5, 5)->minus(years: 1, months: 2, days: 3);
        self::assertEquals(new Duration(4, 3, 2), $d);
    }

    public function testHandlesLargeNumbers(): void
    {
        $duration = new Duration(9999, 99, 366, 48, 120, 3661, 2_000_000_000);
        self::assertSame(
            'P9999Y99M366DT48H120M3661.2S',
            $duration->format()
        );
    }

    public function testParsingInvalidFormatThrowsException(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::parse('NotADuration');
    }

    public function testParsingInvalidExtendedFormatThrowsException(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::parse('P9999-13-40T25:61:9999', DurationFormat::IsoExtended);
    }

}
