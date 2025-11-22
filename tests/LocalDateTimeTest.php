<?php declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\DayOfWeek;
use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\TemporalFields;
use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\InsufficientTimeComponents;
use Brzuchal\DateTime\InvalidDate;
use Brzuchal\DateTime\InvalidTime;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\Temporal\Adjusters\DayOfWeekAdjuster;
use Brzuchal\DateTime\Temporal\TemporalField;
use Brzuchal\DateTime\Temporal\TemporalQueries;
use Brzuchal\DateTime\Temporal\TimeUnit;
use PHPUnit\Framework\TestCase;

final class LocalDateTimeTest extends TestCase
{
    public function testOfBuildsFromComponents(): void
    {
        $dateTime = LocalDateTime::of(2024, 3, 15, 9, 30, 45, 123_456_789);

        self::assertSame(2024, $dateTime->year);
        self::assertSame(3, $dateTime->month);
        self::assertSame(15, $dateTime->day);
        self::assertSame(9, $dateTime->hour);
        self::assertSame(30, $dateTime->minute);
        self::assertSame(45, $dateTime->second);
        self::assertSame(123_456_789, $dateTime->nano);
    }

    public function testOfRejectsInvalidDate(): void
    {
        $this->expectException(InvalidDate::class);
        LocalDateTime::of(2024, 13, 1);
    }

    public function testOfRejectsInvalidTime(): void
    {
        $this->expectException(InvalidTime::class);
        LocalDateTime::of(2024, 1, 1, 24);
    }

    public function testFromDateAndTime(): void
    {
        $date = LocalDate::of(1999, 12, 31);
        $time = LocalTime::of(23, 59, 59, 999_999_999);

        $dateTime = LocalDateTime::ofDateAndTime($date, $time);

        self::assertSame((string) $date, (string) $dateTime->toLocalDate());
        self::assertSame((string) $time, (string) $dateTime->toLocalTime());
    }

    public function testOfInstant(): void
    {
        $instant = Instant::of(86_401); // 1970-01-02T00:00:01Z
        $dateTime = LocalDateTime::ofInstant($instant);

        self::assertSame('1970-01-02T00:00:01', (string) $dateTime);
    }

    public function testOfInstantHandlesMaxNanoAdjustment(): void
    {
        $instant = new Instant(Instant::TICKS_PER_SECOND - 1); // 1970-01-01T00:00:00.9999999Z

        self::assertSame('1970-01-01T00:00:00.9999999', (string) LocalDateTime::ofInstant($instant));
    }

    public function testOfInstantHandlesNegativeRemainder(): void
    {
        $instant = new Instant(-1); // 1969-12-31T23:59:59.9999999Z

        self::assertSame('1969-12-31T23:59:59.9999999', (string) LocalDateTime::ofInstant($instant));
    }

    public function testOfInstantBeforeEpoch(): void
    {
        $instant = Instant::of(-TimeUnit::SECONDS_PER_DAY + 1); // 1969-12-31T00:00:01Z

        self::assertSame('1969-12-31T00:00:01', (string) LocalDateTime::ofInstant($instant));
    }

    public function testPlusDurationHugeNanoOverflow(): void
    {
        $dateTime = LocalDateTime::of(2023, 8, 20, 12, 0, 0, 500);
        $nanos = (2 * TimeUnit::NANOS_PER_DAY) + (3 * TimeUnit::NANOS_PER_HOUR) + 250;

        $result = $dateTime->plus(new Duration(nanos: $nanos));

        self::assertSame('2023-08-22T15:00:00.00000075', (string) $result);
    }

    public function testPlusWithZeroDurationReturnsSameInstance(): void
    {
        $dateTime = LocalDateTime::of(2020, 1, 1, 1, 1, 1, 1);

        self::assertSame($dateTime, $dateTime->plus(new Duration()));
    }

    public function testWithRejectsInvalidHour(): void
    {
        $dateTime = LocalDateTime::of(2020, 1, 1, 1, 1, 1, 1);

        $this->expectException(InvalidTime::class);
        $dateTime->with(hour: 24);
    }

    public function testTemporalAccessorFields(): void
    {
        $dateTime = LocalDateTime::of(2024, 4, 1, 16, 5, 6);

        self::assertTrue($dateTime->supports(TemporalField::Year, TemporalField::Hour, TemporalField::Minute, TemporalField::WeekOfYear));
        self::assertSame(2024, $dateTime->get(TemporalField::Year));
        self::assertSame(16, $dateTime->get(TemporalField::Hour));
        self::assertSame(4, $dateTime->get(TemporalField::Month));
        self::assertSame(1, $dateTime->get(TemporalField::Day));
        self::assertSame(16, $dateTime->hour);
        self::assertSame(1, $dateTime->get(TemporalField::AmPm));
        self::assertSame(0, $dateTime->get(TemporalField::Nano));
    }

    public function testCompareToAndEqualTo(): void
    {
        $first = LocalDateTime::of(2020, 5, 10, 9);
        $second = LocalDateTime::of(2020, 5, 10, 10);

        self::assertSame(-1, $first->compareTo($second));
        self::assertSame(1, $second->compareTo($first));
        self::assertTrue($first->equalTo(LocalDateTime::of(2020, 5, 10, 9)));
    }

    public function testPlusConvenienceMethods(): void
    {
        $dateTime = LocalDateTime::of(2000, 1, 1);

        $advanced = $dateTime
            ->plusYears(1)
            ->plusMonths(1)
            ->plusDays(1)
            ->plusHours(1)
            ->plusMinutes(1)
            ->plusSeconds(1)
            ->plusNanos(1_000);

        self::assertSame('2001-02-02T01:01:01.000001', (string) $advanced);
    }

    public function testWithAdjustments(): void
    {
        $dateTime = LocalDateTime::of(1985, 10, 25, 1, 20, 0, 123);

        $adjusted = $dateTime->with(hour: 10, minute: 4, nano: 456);
        self::assertSame('1985-10-25T10:04:00.000000456', (string) $adjusted);

        self::assertSame($dateTime, $dateTime->with());
        self::assertSame($dateTime, $dateTime->withTime($dateTime->time));
        self::assertSame($dateTime, $dateTime->withDate($dateTime->date));
    }

    public function testSerializeProducesCompactPayload(): void
    {
        $dateTime = LocalDateTime::of(2021, 6, 7, 8, 9, 10, 123_456_789);

        self::assertSame(
            ['value' => '2021-06-07T08:09:10.123456789'],
            $dateTime->__serialize(),
        );

        $roundTripped = \unserialize(\serialize($dateTime));

        self::assertInstanceOf(LocalDateTime::class, $roundTripped);
        self::assertSame((string) $dateTime, (string) $roundTripped);
    }

    public function testRequiredFieldsContainDateAndTime(): void
    {
        self::assertSame(
            [
                TemporalField::Year,
                TemporalField::Month,
                TemporalField::Day,
                TemporalField::Hour,
                TemporalField::Minute,
                TemporalField::Second,
            ],
            LocalDateTime::requires(),
        );
    }

    public function testFromTemporalAccessor(): void
    {
        $fields = new TemporalFields(
            year: 2024,
            month: 6,
            day: 18,
            hour: 11,
            minute: 22,
            second: 33,
            nano: 444,
        );

        $dateTime = LocalDateTime::from($fields);

        self::assertSame('2024-06-18T11:22:33.000000444', (string) $dateTime);
    }

    public function testFromTemporalAccessorMissingSecondThrows(): void
    {
        $fields = new TemporalFields(year: 2024, month: 6, day: 18, hour: 11, minute: 22);

        $this->expectException(InsufficientTimeComponents::class);

        LocalDateTime::from($fields);
    }

    public function testQueryReturnsLocalDateTime(): void
    {
        $fields = new TemporalFields(
            year: 2024,
            month: 12,
            day: 1,
            hour: 7,
            minute: 30,
            second: 0,
        );

        $dateTime = $fields->query(TemporalQueries::localDateTime());

        self::assertInstanceOf(LocalDateTime::class, $dateTime);
        self::assertSame('2024-12-01T07:30:00', (string) $dateTime);
    }

    public function testQueryReturnsNullWhenMissingDateTimeFields(): void
    {
        $fields = new TemporalFields(year: 2024, hour: 10);

        self::assertNull($fields->query(TemporalQueries::localDateTime()));
    }

    public function testAdjustNextFriday(): void
    {
        $wednesday = LocalDateTime::of(2024, 6, 12, 9, 0); // Wednesday
        $adjusted = $wednesday->adjust(DayOfWeekAdjuster::next(DayOfWeek::Friday));

        self::assertSame('2024-06-14T09:00:00', (string) $adjusted);
    }
}
