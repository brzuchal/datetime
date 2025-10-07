<?php declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\InvalidTime;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\Temporal\TemporalField;
use Brzuchal\DateTime\Temporal\TimeUnit;
use PHPUnit\Framework\TestCase;

final class LocalTimeTest extends TestCase
{
    public function testOfSetsComponents(): void
    {
        $time = LocalTime::of(13, 45, 27, 123_000_456);

        self::assertSame(13, $time->hour);
        self::assertSame(45, $time->minute);
        self::assertSame(27, $time->second);
        self::assertSame(123_000_456, $time->nano);
    }

    public function testOfRejectsOutOfRangeValues(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::of(24);
    }

    public function testOfRejectsNegativeValues(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::of(10, -1);
    }

    public function testOfRejectsSecondOverflow(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::of(10, 0, 60);
    }

    public function testOfRejectsNanoOverflow(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::of(10, 0, 0, TimeUnit::NANOS_PER_SECOND);
    }

    public function testBoundaryValues(): void
    {
        $midnight = LocalTime::of(0, 0, 0, 0);
        $max = LocalTime::of(23, 59, 59, TimeUnit::NANOS_PER_SECOND - 1);

        self::assertSame(0, $midnight->toSecondOfDay());
        self::assertSame(TimeUnit::SECONDS_PER_DAY - 1, $max->toSecondOfDay());
        self::assertSame(TimeUnit::NANOS_PER_SECOND - 1, $max->nano);
    }

    public function testOfNanoOfDay(): void
    {
        $time = LocalTime::ofNanoOfDay(18 * 3_600 * 1_000_000_000 + 12_345_678);

        self::assertSame(18, $time->hour);
        self::assertSame(0, $time->minute);
        self::assertSame(0, $time->second);
        self::assertSame(12_345_678, $time->nano);
    }

    public function testOfSecondOfDayRejectsOutOfRange(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::ofSecondOfDay(TimeUnit::SECONDS_PER_DAY);
    }

    public function testOfSecondOfDayRejectsNegative(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::ofSecondOfDay(-1);
    }

    public function testOfNanoOfDayRejectsOutOfRange(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::ofNanoOfDay(TimeUnit::NANOS_PER_DAY);
    }

    public function testOfNanoOfDayRejectsNegative(): void
    {
        $this->expectException(InvalidTime::class);
        LocalTime::ofNanoOfDay(-1);
    }

    public function testPlusWrapsAcrossMidnight(): void
    {
        $time = LocalTime::of(23, 50)->plus(minutes: 20);

        self::assertSame('00:10:00', (string) $time);
    }

    public function testPlusHandlesLargeOffsets(): void
    {
        $time = LocalTime::of(3, 15, 0, 250)->plus(hours: 48, minutes: 1440, seconds: 90, nanos: 750);

        self::assertSame('03:16:30.000001', (string) $time);
    }

    public function testMinusHandlesNegativeOverflow(): void
    {
        $time = LocalTime::of(0, 15)->minus(minutes: 30);

        self::assertSame('23:45:00', (string) $time);
    }

    public function testMinusHandlesNanosecondBorrow(): void
    {
        $time = LocalTime::of(1, 0, 0, 10)->minus(nanos: 20);

        self::assertSame('00:59:59.99999999', (string) $time);
    }

    public function testWithNanoOfDayReturnsSameInstanceWhenUnchanged(): void
    {
        $time = LocalTime::of(12, 34, 56, 789);

        self::assertSame($time, $time->withNanoOfDay($time->toNanoOfDay()));
    }

    public function testWithNanoOfDayRejectsOutOfRange(): void
    {
        $time = LocalTime::of(10, 0);

        $this->expectException(InvalidTime::class);
        $time->withNanoOfDay(TimeUnit::NANOS_PER_DAY);
    }

    public function testTemporalAccessorFields(): void
    {
        $time = LocalTime::of(21, 5, 9);

        self::assertTrue($time->supports(TemporalField::Hour, TemporalField::Minute, TemporalField::Second));
        self::assertFalse($time->supports(TemporalField::Year));

        self::assertSame(21, $time->get(TemporalField::Hour));
        self::assertSame(9, $time->get(TemporalField::Second));
        self::assertSame(21 * 3_600 + 5 * 60 + 9, intdiv($time->toNanoOfDay(), 1_000_000_000));
        self::assertSame(9, $time->get(TemporalField::Hour12));
        self::assertSame(1, $time->get(TemporalField::AmPm));
    }

    public function testCompareToOrdersByNanoOfDay(): void
    {
        $earlier = LocalTime::of(6, 30);
        $later = LocalTime::of(6, 30, 0, 1);

        self::assertSame(-1, $earlier->compareTo($later));
        self::assertSame(1, $later->compareTo($earlier));
        self::assertTrue($earlier->equals(LocalTime::of(6, 30)));
    }

    public function testToNanoOfDayAndSecondsAreConsistent(): void
    {
        $time = LocalTime::of(5, 4, 3, 2);

        $expectedSeconds = 5 * TimeUnit::SECONDS_PER_HOUR
            + 4 * TimeUnit::SECONDS_PER_MINUTE
            + 3;

        self::assertSame($expectedSeconds, $time->toSecondOfDay());
        self::assertSame(
            $expectedSeconds * TimeUnit::NANOS_PER_SECOND + 2,
            $time->toNanoOfDay(),
        );
    }

    public function testToStringIncludesFractionalSeconds(): void
    {
        $time = LocalTime::of(12, 0, 0, 987_654_000);

        self::assertSame('12:00:00.987654', (string) $time);
    }

    public function testSerializeProducesMinimalPayload(): void
    {
        $time = LocalTime::of(7, 8, 9, 123_400_000);

        self::assertSame(['time' => '07:08:09.1234'], $time->__serialize());

        $roundTripped = \unserialize(\serialize($time));

        self::assertInstanceOf(LocalTime::class, $roundTripped);
        self::assertSame((string) $time, (string) $roundTripped);
    }
}
