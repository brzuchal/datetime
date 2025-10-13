<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\LocalDate;

/**
 * ISO-8601 proleptic Gregorian calendar arithmetic.
 */
final class IsoCalendar
{
    private const int MONTHS_PER_YEAR = 12;
    private const int DAYS_PER_CYCLE = 146097; // 400-year cycle
    private const int DAYS_0000_TO_1970 = self::DAYS_PER_CYCLE * 5 - (30 * 365) - 7 - 59;
    private const array MONTH_LENGTHS = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    private const array MONTH_LENGTHS_LEAP = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    /**
     * Days elapsed before the first day of each month (1-based index) in a common year.
     */
    private const array DAYS_BEFORE_MONTH = [
        0,   // January
        31,  // February
        59,  // March
        90,  // April
        120, // May
        151, // June
        181, // July
        212, // August
        243, // September
        273, // October
        304, // November
        334, // December
    ];

    /**
     * Days elapsed before the first day of each month (1-based index) in a leap year.
     */
    private const array DAYS_BEFORE_MONTH_LEAP = [
        0,   // January
        31,  // February
        60,  // March
        91,  // April
        121, // May
        152, // June
        182, // July
        213, // August
        244, // September
        274, // October
        305, // November
        335, // December
    ];

    /**
     * @return array{0:int,1:int<1,12>,2:int<1,31>}
     */
    public static function dateFromEpochDay(int $epochDay): array
    {
        return self::computeDateFromEpochDay($epochDay);
    }

    public static function epochDayFromDate(int $year, int $month, int $day): int
    {
        $monthLength = self::monthLength($year, $month);
        if ($day < 1 || $day > $monthLength) {
            throw new \InvalidArgumentException('Invalid day: ' . $day . ' for month ' . $month);
        }

        $dayOfYear = self::dayOfYearUnchecked($year, $month, $day);

        $total = self::yearStartDay($year) + $dayOfYear;

        return $total - self::DAYS_0000_TO_1970;
    }

    public static function isValidDate(int $year, int $month, int $day): bool
    {
        if ($month < 1 || $month > self::MONTHS_PER_YEAR) {
            return false;
        }

        if ($day < 1) {
            return false;
        }

        return $day <= self::monthLength($year, $month);
    }

    /**
     * @return int<1, 366>
     */
    public static function dayOfYear(int $year, int $month, int $day): int
    {
        if (! self::isValidDate($year, $month, $day)) {
            throw new \InvalidArgumentException('Invalid date: ' . $year . '-' . $month . '-' . $day);
        }

        return self::dayOfYearUnchecked($year, $month, $day);
    }

    public static function yearsMonthsToDays(int $year, int $month, int $day, int $years, int $months): int
    {
        if ($years === 0 && $months === 0) {
            return 0;
        }

        [$newYear, $newMonth, $normalizedDay] = self::adjustDate($year + $years, $month + $months, $day);

        if ($newYear === 0) {
            $newYear = ($years * self::MONTHS_PER_YEAR + $months) >= 0 ? 1 : -1;
        }

        $targetLength = self::monthLength($newYear, $newMonth);
        if ($normalizedDay > $targetLength) {
            $normalizedDay = $targetLength;
        }

        return self::epochDayFromDate($newYear, $newMonth, $normalizedDay)
            - self::epochDayFromDate($year, $month, $day);
    }

    public static function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0) && (($year % 100 !== 0) || ($year % 400 === 0));
    }

    public static function dateFromInstant(Instant $instant): LocalDate
    {
        return LocalDate::fromEpochDay($instant->epochDay);
    }

    /**
     * @return array{0:int,1:int<1,12>,2:int<1,31>}
     */
    private static function computeDateFromEpochDay(int $epochDay): array
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

    /**
     * @param int<1,12> $month
     * @param int<1,31> $day
     * @return int<1,366>
     */
    private static function dayOfYearUnchecked(int $year, int $month, int $day): int
    {
        $index = $month - 1;
        $daysBeforeMonth = self::isLeapYear($year)
            ? self::DAYS_BEFORE_MONTH_LEAP[$index]
            : self::DAYS_BEFORE_MONTH[$index];

        return $daysBeforeMonth + $day;
    }

    private static function yearStartDay(int $year): int
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
            - (self::isLeapYear($year) ? 60 : 59);
    }

    /**
     * @return array{0:int,1:int<1,12>,2:int<1,31>}
     */
    private static function adjustDate(int $year, int $month, int $day): array
    {
        while ($month > self::MONTHS_PER_YEAR) {
            $month -= self::MONTHS_PER_YEAR;
            $year++;
        }

        while ($month < 1) {
            $month += self::MONTHS_PER_YEAR;
            $year--;
        }

        while ($day < 1) {
            $month--;
            if ($month < 1) {
                $month = self::MONTHS_PER_YEAR;
                $year--;
            }

            $day += self::monthLength($year, $month);
        }

        $monthLength = self::monthLength($year, $month);

        if ($month === 2 && $day === 29 && ! self::isLeapYear($year)) {
            $day = 28;
        }

        if ($day > $monthLength) {
            $day = $monthLength;
        }

        assert($day <= $monthLength);

        return [$year, $month, $day];
    }

    /**
     * @param int<1,12> $month
     * @return int<1,31>
     */
    private static function monthLength(int $year, int $month): int
    {
        self::ensureValidMonth($month);

        $index = $month - 1;

        return self::isLeapYear($year)
            ? self::MONTH_LENGTHS_LEAP[$index]
            : self::MONTH_LENGTHS[$index];
    }

    private static function ensureValidMonth(int $month): void
    {
        if ($month < 1 || $month > self::MONTHS_PER_YEAR) {
            throw new \InvalidArgumentException('Month must be between 1 and 12, got ' . $month);
        }
    }
}
