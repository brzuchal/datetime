<?php declare(strict_types=1);

namespace Tests\Calendars;

use Brzuchal\DateTime\CalendarSystems\IsoCalendarSystem;
use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\LocalDate;
use PHPUnit\Framework\TestCase;

final class IsoCalendarSystemTest extends TestCase
{
    private IsoCalendarSystem $calendar;

    protected function setUp(): void
    {
        $this->calendar = new IsoCalendarSystem();
    }

    public function testEpochDayZeroIs1970_01_01(): void
    {
        $date = $this->calendar->dateFromEpochDay(0);
        self::assertSame([1970, 1, 1], $date);
    }

    public function testEpochDay1970_01_01IsZero(): void
    {
        $epoch = $this->calendar->epochDayFromDate(1970, 1, 1);
        self::assertSame(0, $epoch);
    }

    public function testDateFromEpochDayNegative(): void
    {
        $date = $this->calendar->dateFromEpochDay(-719468); // 0000-03-01
        self::assertSame([0, 3, 1], $date);
    }

    public function testEpochDayRoundTrip(): void
    {
        $expected = [2024, 2, 29]; // Leap year date
        $epoch = $this->calendar->epochDayFromDate(...$expected);
        $actual = $this->calendar->dateFromEpochDay($epoch);

        self::assertSame($expected, $actual);
    }

    public function testInstantToDate(): void
    {
        $instant = new Instant(0); // 1970-01-01T00:00:00Z
        $date = $this->calendar->date($instant);

        self::assertSame(1970, $date->year);
        self::assertSame(1, $date->month);
        self::assertSame(1, $date->day);
    }

    public function testInstantNegativeEpoch(): void
    {
        $instant = new Instant(-22089899720000000);
        $date = $this->calendar->date($instant);

        self::assertSame(1900, $date->year);
        self::assertSame(1, $date->month);
        self::assertSame(1, $date->day);
    }

    public function testEraOfCommonEra(): void
    {
        $date = LocalDate::of(2024, 3, 1, $this->calendar);
        $era = $this->calendar->eraOf($date->year, $date->month, $date->day);

        self::assertSame('CE', $era->code);
    }

    public function testEraOfBeforeCommonEra(): void
    {
        $date = LocalDate::of(-1, 12, 31, $this->calendar);
        $era = $this->calendar->eraOf($date->year, $date->month, $date->day);

        self::assertSame('BCE', $era->code);
    }
}
