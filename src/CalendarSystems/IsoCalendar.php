<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\LocalDate;

/**
 * ISO-8601 proleptic Gregorian calendar arithmetic.
 */
final class IsoCalendar extends BaseGJCalendar
{
    private const int DAYS_0000_TO_1970 = self::DAYS_PER_CYCLE * 5 - (30 * 365) - 7 - 59;

    /**
     * @return array{0:int,1:int<1,12>,2:int<1,31>}
     */
    public static function dateFromEpochDay(int $epochDay): array
    {
        return self::computeDateFromEpochDay($epochDay);
    }

    public static function epochDayFromDate(int $year, int $month, int $day): int
    {
        return self::computeEpochDayFromDate($year, $month, $day);
    }

    public static function isLeapYear(int $year): bool
    {
        return self::calendarLeapYear($year);
    }

    public static function dateFromInstant(Instant $instant): LocalDate
    {
        return LocalDate::fromEpochDay($instant->epochDay);
    }

    protected static function calendarLeapYear(int $year): bool
    {
        return ($year % 4 === 0) && (($year % 100 !== 0) || ($year % 400 === 0));
    }

    protected static function epochDayOffset(): int
    {
        return self::DAYS_0000_TO_1970;
    }

    /**
     * @return array{0:int,1:int<1,12>,2:int<1,31>}
     */
    protected static function computeDateFromEpochDay(int $epochDay): array
    {
        $offset = self::DAYS_0000_TO_1970 - 1;
        $n = $epochDay + $offset;

        if ($n >= 0) {
            $era = \intdiv($n, self::DAYS_PER_CYCLE);
        } else {
            $era = \intdiv($n - self::DAYS_PER_CYCLE + 1, self::DAYS_PER_CYCLE);
        }

        $doe = $n - $era * self::DAYS_PER_CYCLE;
        $yoe = \intdiv(
            $doe
            - \intdiv($doe, 1460)
            + \intdiv($doe, 36524)
            - \intdiv($doe, self::DAYS_PER_CYCLE + 1),
            365,
        );

        $year = $yoe + $era * 400;
        $doy = $doe - (365 * $yoe + \intdiv($yoe, 4) - \intdiv($yoe, 100));
        $mp = \intdiv(5 * $doy + 2, 153);

        $day = $doy - \intdiv(153 * $mp + 2, 5) + 1;
        assert($day >= 1 && $day <= 31);

        $month = $mp < 10 ? $mp + 3 : $mp - 9;
        assert($month >= 1 && $month <= 12);

        if ($month <= 2) {
            $year++;
        }

        return [$year, $month, $day];
    }

    protected static function computeEpochDayFromDate(int $year, int $month, int $day): int
    {
        $total = self::yearStartDay($year);
        $daysToAdd = 0;

        for ($i = 1; $i < $month; $i++) {
            $daysToAdd += parent::monthLength($year, $i);
        }

        $total += $daysToAdd + $day;

        return $total - self::DAYS_0000_TO_1970;
    }

    protected static function yearStartDay(int $year): int
    {
        $cycles = \intdiv($year, 400);
        $yearInCycle = $year - $cycles * 400;
        if ($yearInCycle < 0) {
            --$cycles;
            $yearInCycle += 400;
        }

        return $cycles * self::DAYS_PER_CYCLE
            + $yearInCycle * 365
            + \intdiv($yearInCycle, 4)
            - \intdiv($yearInCycle, 100)
            - (self::calendarLeapYear($year) ? 60 : 59);
    }
}
