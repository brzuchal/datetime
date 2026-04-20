<?php

declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\DurationFormat;
use Brzuchal\DateTime\InvalidDuration;
use PHPUnit\Framework\TestCase;

final class DurationTest extends TestCase
{
    public function testCanBeCreatedFromNamedArguments(): void
    {
        $duration = new Duration(hours: 4, minutes: 5, seconds: 6, nanos: 7);

        self::assertSame(4, $duration->hours);
        self::assertSame(5, $duration->minutes);
        self::assertSame(6, $duration->seconds);
        self::assertSame(7, $duration->nanos);
    }

    public function testParsingStandardFormat(): void
    {
        $duration = Duration::parse('PT4H5M6.789S');
        self::assertEquals(new Duration(4, 5, 6, 789_000_000), $duration);
    }

    public function testParsingExtendedFormat(): void
    {
        $duration = Duration::parse('T04:05:06', DurationFormat::IsoExtended);
        self::assertEquals(new Duration(4, 5, 6), $duration);
    }

    public function testParsingExtendedFormatWithFractionalSeconds(): void
    {
        $duration = Duration::parse('T04:05:06.789000123', DurationFormat::IsoExtended);
        self::assertEquals(new Duration(4, 5, 6, 789_000_123), $duration);
    }

    public function testFormattingExtendedFormatWithFractionalSeconds(): void
    {
        $duration = new Duration(4, 5, 6, 789_000_000);
        self::assertSame('T04:05:06.789', $duration->format(DurationFormat::IsoExtended));
    }

    public function testFormattingStandardFormat(): void
    {
        $duration = new Duration(4, 5, 6, 789_000_000);
        self::assertSame('PT4H5M6.789S', $duration->format());
    }

    public function testFormattingExtendedFormat(): void
    {
        $duration = new Duration(4, 5, 6);
        self::assertSame('T04:05:06', $duration->format(DurationFormat::IsoExtended));
    }

    public function testToStringDefaultsToStandardFormat(): void
    {
        $duration = new Duration(1, 0, 0);
        self::assertSame('PT1H', (string) $duration);
    }

    public function testPlusMethod(): void
    {
        $d = new Duration(1, 2, 3)->plus(hours: 10);
        $expected = new Duration(11, 2, 3);
        self::assertEquals($expected, $d);
    }

    public function testMinusMethod(): void
    {
        $d = new Duration(5, 5, 5)->minus(hours: 1, minutes: 2, seconds: 3);
        self::assertEquals(new Duration(4, 3, 2), $d);
    }

    public function testMinusHandlesNanoseconds(): void
    {
        $d = new Duration(seconds: 10, nanos: 800_000_000)->minus(nanos: 500_000_000);

        self::assertEquals(new Duration(seconds: 10, nanos: 300_000_000), $d);
    }

    public function testHandlesLargeNumbers(): void
    {
        $duration = new Duration(48, 120, 3661, 2_000_000_000);
        self::assertSame(
            'PT48H120M3661.2S',
            $duration->format(),
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
        Duration::parse('T25:61:9999', DurationFormat::IsoExtended);
    }

    public function testParsingFractionalHoursIsRejected(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::parse('PT1.25H');
    }

    public function testParsingExtendedFormatRejectsFractionalHours(): void
    {
        $this->expectException(InvalidDuration::class);
        Duration::parse('T04.5:05:06', DurationFormat::IsoExtended);
    }

    public function testPlusDurationAddsAnotherDuration(): void
    {
        $duration1 = new Duration(hours: 4, minutes: 5, seconds: 6, nanos: 7);
        $duration2 = new Duration(hours: 1, minutes: 1, seconds: 1, nanos: 1);

        $result = $duration1->plusDuration($duration2);

        self::assertSame(5, $result->hours);
        self::assertSame(6, $result->minutes);
        self::assertSame(7, $result->seconds);
        self::assertSame(8, $result->nanos);
    }

    public function testMinusDurationSubtractsAnotherDuration(): void
    {
        $duration1 = new Duration(hours: 5, minutes: 6, seconds: 7, nanos: 8);
        $duration2 = new Duration(hours: 1, minutes: 1, seconds: 1, nanos: 1);

        $result = $duration1->minusDuration($duration2);

        self::assertSame(4, $result->hours);
        self::assertSame(5, $result->minutes);
        self::assertSame(6, $result->seconds);
        self::assertSame(7, $result->nanos);
    }

    public function testMultipliedByMultipliesAllComponents(): void
    {
        $duration = new Duration(hours: 4, minutes: 5, seconds: 6, nanos: 7);
        $result = $duration->multipliedBy(2);

        self::assertSame(8, $result->hours);
        self::assertSame(10, $result->minutes);
        self::assertSame(12, $result->seconds);
        self::assertSame(14, $result->nanos);
    }

    public function testNegatedNegatesAllComponents(): void
    {
        $duration = new Duration(hours: 4, minutes: 5, seconds: 6, nanos: 7);
        $result = $duration->negated();

        self::assertSame(-4, $result->hours);
        self::assertSame(-5, $result->minutes);
        self::assertSame(-6, $result->seconds);
        self::assertSame(-7, $result->nanos);
    }
}
