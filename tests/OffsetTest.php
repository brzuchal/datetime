<?php

declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\InvalidOffset;
use Brzuchal\DateTime\Offset;
use PHPUnit\Framework\TestCase;

class OffsetTest extends TestCase
{
    public function testUtc(): void
    {
        $offset = Offset::utc();

        self::assertSame(0, $offset->totalSeconds);
        self::assertSame('Z', (string) $offset);
    }

    public function testOfTotalSeconds(): void
    {
        $offset = Offset::ofTotalSeconds(7200); // +02:00

        self::assertSame(7200, $offset->totalSeconds);
        self::assertSame('+02:00', (string) $offset);
    }

    public function testOfTotalSecondsNegative(): void
    {
        $offset = Offset::ofTotalSeconds(-18000); // -05:00

        self::assertSame(-18000, $offset->totalSeconds);
        self::assertSame('-05:00', (string) $offset);
    }

    public function testOfTotalSecondsWithSeconds(): void
    {
        $offset = Offset::ofTotalSeconds(19845); // +05:30:45

        self::assertSame(19845, $offset->totalSeconds);
        self::assertSame('+05:30:45', (string) $offset);
    }

    public function testOfTotalSecondsOutOfRangePositive(): void
    {
        $this->expectException(InvalidOffset::class);
        $this->expectExceptionMessage('Zone offset not in valid range');

        Offset::ofTotalSeconds(64801);
    }

    public function testOfTotalSecondsOutOfRangeNegative(): void
    {
        $this->expectException(InvalidOffset::class);
        $this->expectExceptionMessage('Zone offset not in valid range');

        Offset::ofTotalSeconds(-64801);
    }

    public function testOf(): void
    {
        $offset = Offset::of(2, 30); // +02:30

        self::assertSame(9000, $offset->totalSeconds);
        self::assertSame('+02:30', (string) $offset);
    }

    public function testOfWithSeconds(): void
    {
        $offset = Offset::of(5, 30, 45); // +05:30:45

        self::assertSame(19845, $offset->totalSeconds);
        self::assertSame('+05:30:45', (string) $offset);
    }

    public function testOfNegative(): void
    {
        $offset = Offset::of(-5, -30); // -05:30

        self::assertSame(-19800, $offset->totalSeconds);
        self::assertSame('-05:30', (string) $offset);
    }

    public function testOfHoursOutOfRange(): void
    {
        $this->expectException(InvalidOffset::class);
        $this->expectExceptionMessage('Zone offset hours not in valid range');

        Offset::of(19);
    }

    public function testOfMinutesInvalidSign(): void
    {
        $this->expectException(InvalidOffset::class);
        $this->expectExceptionMessage('Zone offset minutes and seconds must be positive for positive hours');

        Offset::of(2, -30);
    }

    public function testParse(): void
    {
        $offset = Offset::parse('+02:00');

        self::assertSame(7200, $offset->totalSeconds);
        self::assertSame('+02:00', (string) $offset);
    }

    public function testParseZ(): void
    {
        $offset = Offset::parse('Z');

        self::assertSame(0, $offset->totalSeconds);
        self::assertSame('Z', (string) $offset);
    }

    public function testParseWithSeconds(): void
    {
        $offset = Offset::parse('+05:30:45');

        self::assertSame(19845, $offset->totalSeconds);
        self::assertSame('+05:30:45', (string) $offset);
    }

    public function testParseCompactFormat(): void
    {
        $offset = Offset::parse('+0200');

        self::assertSame(7200, $offset->totalSeconds);
        self::assertSame('+02:00', (string) $offset);
    }

    public function testParseHoursOnly(): void
    {
        $offset = Offset::parse('+05');

        self::assertSame(18000, $offset->totalSeconds);
        self::assertSame('+05:00', (string) $offset);
    }

    public function testParseNegative(): void
    {
        $offset = Offset::parse('-05:30');

        self::assertSame(-19800, $offset->totalSeconds);
        self::assertSame('-05:30', (string) $offset);
    }

    public function testParseInvalidFormat(): void
    {
        $this->expectException(InvalidOffset::class);
        $this->expectExceptionMessage('Invalid zone offset format');

        Offset::parse('invalid');
    }

    public function testCompareTo(): void
    {
        $offset1 = Offset::of(2);
        $offset2 = Offset::of(5);
        $offset3 = Offset::of(-3);

        self::assertLessThan(0, $offset1->compareTo($offset2));
        self::assertGreaterThan(0, $offset1->compareTo($offset3));
        self::assertSame(0, $offset1->compareTo(Offset::of(2)));
    }

    public function testEqualTo(): void
    {
        $offset1 = Offset::of(2, 30);
        $offset2 = Offset::of(2, 30);
        $offset3 = Offset::of(2, 0);

        self::assertTrue($offset1->equalTo($offset2));
        self::assertFalse($offset1->equalTo($offset3));
    }

    public function testCachingForCommonOffsets(): void
    {
        $offset1 = Offset::of(2); // Common offset (hourly)
        $offset2 = Offset::of(2);

        self::assertSame($offset1, $offset2, 'Common offsets should be cached');
    }

    public function testUtcIsCached(): void
    {
        $utc1 = Offset::utc();
        $utc2 = Offset::utc();

        self::assertSame($utc1, $utc2, 'UTC offset should be cached');
    }
}
