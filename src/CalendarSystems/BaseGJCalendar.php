<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

/**
 * Shared Gregorian/Julian calendar arithmetic used by concrete calendars.
 */
abstract class BaseGJCalendar
{
    protected const int DAYS_PER_CYCLE = 146097;

    /**
     * @phpstan-assert-if-true int<1, 12> $month
     * @phpstan-assert-if-true int<1, 31> $day
     */
    final public static function isValidDate(int $year, int $month, int $day): bool
    {
        if ($month < 1 || $month > 12) {
            return false;
        }

        return $day >= 1 && $day <= static::monthLength($year, $month);
    }

    /**
     * @return int<1, 366>
     */
    final public static function dayOfYear(int $year, int $month, int $day): int
    {
        if (! self::isValidDate($year, $month, $day)) {
            throw new \InvalidArgumentException('Invalid date: ' . $year . '-' . $month . '-' . $day);
        }

        $days = $day;
        for ($m = 1; $m < $month; ++$m) {
            $days += static::monthLength($year, $m);
        }

        assert($days <= 366);

        return $days;
    }

    final public static function yearsMonthsToDays(int $year, int $month, int $day, int $years, int $months): int
    {
        $totalMonthsDelta = $years * 12 + $months;
        [$newYear, $newMonth, $newDay] = self::adjustDate($year + $years, $month + $months, $day);

        if ($newYear === 0) {
            $newYear = $totalMonthsDelta >= 0 ? 1 : -1;
        }

        return static::computeEpochDayFromDate($newYear, $newMonth, $newDay)
            - static::computeEpochDayFromDate($year, $month, $day);
    }

    /**
     * @return int<28, 31>
     */
    protected static function monthLength(int $year, int $month): int
    {
        return match ($month) {
            2 => static::calendarLeapYear($year) ? 29 : 28,
            4, 6, 9, 11 => 30,
            default => 31,
        };
    }

    /**
     * @return array{0:int,1:int<1,12>,2:int<1,31>}
     */
    protected static function computeDateFromEpochDay(int $epochDay): array
    {
        $zeroDay = $epochDay + static::epochDayOffset();
        $zeroDay -= 60;
        $adjust = 0;
        if ($zeroDay < 0) {
            $adjustCycles = \intdiv($zeroDay + 1, self::DAYS_PER_CYCLE) - 1;
            $adjust = $adjustCycles * 400;
            $zeroDay -= $adjustCycles * self::DAYS_PER_CYCLE;
        }

        $yearEst = \intdiv(400 * $zeroDay + 591, self::DAYS_PER_CYCLE);
        $doyEst = $zeroDay - (365 * $yearEst + \intdiv($yearEst, 4) - \intdiv($yearEst, 100) + \intdiv($yearEst, 400));
        if ($doyEst < 0) {
            $yearEst--;
            $doyEst = $zeroDay - (365 * $yearEst + \intdiv($yearEst, 4) - \intdiv($yearEst, 100) + \intdiv($yearEst, 400));
        }

        $marchDoy0 = (int) $doyEst;
        $marchMonth0 = \intdiv($marchDoy0 * 5 + 2, 153);

        $month = ($marchMonth0 + 2) % 12 + 1;
        assert($month >= 1);
        $day = $marchDoy0 - \intdiv($marchMonth0 * 306 + 5, 10) + 1;
        assert($day >= 1 && $day <= 31);

        $year = $yearEst + $adjust + \intdiv($marchMonth0, 10);
        if ($month <= 2) {
            $year++;
        }

        return [$year, $month, $day];
    }

    protected static function computeEpochDayFromDate(int $year, int $month, int $day): int
    {
        $total = 365 * $year;
        if ($year >= 0) {
            $total += \intdiv($year + 3, 4) - \intdiv($year + 99, 100) + \intdiv($year + 399, 400);
        } else {
            $total += \intdiv($year, 4) - \intdiv($year, 100) + \intdiv($year, 400);
        }

        $total += \intdiv(367 * $month - 362, 12) + $day - 1;
        if ($month > 2) {
            $total -= 1;
            if (! static::calendarLeapYear($year)) {
                $total -= 1;
            }
        }

        return $total - static::epochDayOffset();
    }

    /**
     * @return array{0:int,1:int<1,12>,2:int<1,31>}
     */
    private static function adjustDate(int $year, int $month, int $day): array
    {
        while ($month > 12) {
            $month -= 12;
            $year++;
        }

        while ($month < 1) {
            $month += 12;
            $year--;
        }

        while ($day < 1) {
            $month--;
            if ($month < 1) {
                $month = 12;
                $year--;
            }

            $day += static::monthLength($year, $month);
        }

        $monthLength = static::monthLength($year, $month);

        if ($month === 2 && $day === 29 && ! static::calendarLeapYear($year)) {
            $day = 28;
        }

        if ($day > $monthLength) {
            $day = $monthLength;
        }

        assert($day <= $monthLength);

        return [$year, $month, $day];
    }

    abstract protected static function calendarLeapYear(int $year): bool;

    abstract protected static function epochDayOffset(): int;
}
