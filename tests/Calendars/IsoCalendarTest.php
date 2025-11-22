<?php declare(strict_types=1);

namespace Tests\Calendars;

use Brzuchal\DateTime\CalendarSystems\IsoCalendar;
use Brzuchal\DateTime\CalendarSystems\IsoEra;
use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\LocalDate;
use PHPUnit\Framework\TestCase;

final class IsoCalendarTest extends TestCase
{
    public function testEpochDayZeroIs19700101(): void
    {
        $date = IsoCalendar::dateFromEpochDay(0);
        self::assertSame([1970, 1, 1], $date);
    }

    public function testEpochDay19700101IsZero(): void
    {
        $epoch = IsoCalendar::epochDayFromDate(1970, 1, 1);
        self::assertSame(0, $epoch);
    }

    public function testDateFromEpochDayNegative(): void
    {
        $date = IsoCalendar::dateFromEpochDay(-719468); // 0000-03-01
        self::assertSame([0, 3, 1], $date);
    }

    public function testEpochDayRoundTrip(): void
    {
        $expected = [2024, 2, 29]; // Leap year date
        $epoch = IsoCalendar::epochDayFromDate(...$expected);
        $actual = IsoCalendar::dateFromEpochDay($epoch);

        self::assertSame($expected, $actual);
    }

    public function testInstantToDate(): void
    {
        $instant = new Instant(0); // 1970-01-01T00:00:00Z
        $date = IsoCalendar::dateFromInstant($instant);

        self::assertSame(1970, $date->year);
        self::assertSame(1, $date->month);
        self::assertSame(1, $date->day);
    }

    public function testInstantNegativeEpoch(): void
    {
        $instant = new Instant(-22089899720000000);
        $date = IsoCalendar::dateFromInstant($instant);

        self::assertSame(1899, $date->year);
        self::assertSame(12, $date->month);
        self::assertSame(31, $date->day);
    }

    public function testInstantJustBeforeEpoch(): void
    {
        $instant = new Instant(-1);
        $date = IsoCalendar::dateFromInstant($instant);

        self::assertSame(1969, $date->year);
        self::assertSame(12, $date->month);
        self::assertSame(31, $date->day);
    }

    public function testEpochDayForNegativeYears(): void
    {
        $cases = [
            [-1, 1, 1],
            [-1, 3, 1],
            [-1, 12, 31],
            [-2, 3, 1],
            [-400, 3, 1],
            [-401, 1, 1],
            [-401, 3, 1],
        ];

        foreach ($cases as [$year, $month, $day]) {
            $epoch = IsoCalendar::epochDayFromDate($year, $month, $day);
            $date = IsoCalendar::dateFromEpochDay($epoch);

            self::assertSame([$year, $month, $day], $date);
        }
    }

    public function testEraOfCommonEra(): void
    {
        $date = LocalDate::of(2024, 3, 1);
        $era = IsoEra::fromYear($date->year);

        self::assertSame(IsoEra::CommonEra, $era);
    }

    public function testEraOfBeforeCommonEra(): void
    {
        $date = LocalDate::of(-1, 12, 31);
        $era = IsoEra::fromYear($date->year);

        self::assertSame(IsoEra::BeforeCommonEra, $era);
    }
}
