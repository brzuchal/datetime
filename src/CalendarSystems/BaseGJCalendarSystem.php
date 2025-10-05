<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

/**
 * BaseGJCalendarSystem is an abstract class implementing the CalendarSystem interface,
 * providing a foundation for calendar computations based on the proleptic Gregorian and Julian calendar systems.
 *
 * It includes utility methods for determining the structure of a year, calculating epoch days,
 * and converting dates to and from epoch days. The class also handles leap year calculations
 * and date adjustment logic for both positive and negative year ranges.
 */
abstract class BaseGJCalendarSystem implements CalendarSystem
{
    /**
     * The total number of days in a 400-year cycle of the proleptic Gregorian calendar.
     */
    protected const int DAYS_PER_CYCLE = 146097;

    /**
     * Gets the number of months in a given year.
     *
     * @param int $year The year for which the number of months is being retrieved.
     * @return int The number of months in the year.
     */
    public function monthsInYear(int $year): int
    {
        return 12;
    }

    /**
     * Determines the number of days in a specific month of a given year.
     *
     * @param int $year  The year for which the month's length is being calculated.
     * @param int<1,12> $month The month (1-12) for which the number of days is being determined.
     * @return int<28,31> The number of days in the specified month of the given year.
     */
    public function monthLength(int $year, int $month): int
    {
        return match ($month) {
            2 => $this->isLeapYear($year) ? 29 : 28,
            4, 6, 9, 11 => 30,
            default => 31,
        };
    }

    /**
     * Determines the number of days in a given year.
     *
     * @param int $year The year for which the length in days is being calculated.
     * @return int The number of days in the year (365 for a common year, 366 for a leap year).
     */
    public function yearLength(int $year): int
    {
        return $this->isLeapYear($year) ? 366 : 365;
    }

    /**
     * Calculates the day of the year for a given date.
     *
     * @param int $year  The year of the date.
     * @param int<1,12> $month The month of the date (1-12).
     * @param int<1,31> $day   The day of the month.
     * @return int<1,366> The day of the year corresponding to the given date.
     */
    public function dayOfYear(int $year, int $month, int $day): int
    {
        $days = $day;
        for ($m = 1; $m < $month; ++$m) {
            $days += $this->monthLength($year, $m);
        }

        assert($days <= 366);

        return $days;
    }

    /**
     * Determines the month and day within a year from the given day of the year.
     *
     * @param int<1,366> $dayOfYear The day of the year (1 to 365, or 1 to 366 for leap years).
     * @param bool $leap      Indicates whether the year is a leap year.
     * @return array{0:int<1,12>,1:int<1,31>} An array containing two elements: the month (int) and the day (int) within that month.
     * @throws DayOfYearOutOfBounds If the dayOfYear is invalid for the given year type.
     * @throws InvalidDayOfYear If the dayOfYear cannot be resolved to a valid month and day.
     */
    protected function monthDayFromDayOfYear(int $dayOfYear, bool $leap): array
    {
        if (!$leap && $dayOfYear > 365) {
            throw new DayOfYearOutOfBounds('Invalid dayOfYear: ' . $dayOfYear . '.');
        }

        $monthLengths = [31, ($leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $month = 1;
        foreach ($monthLengths as $length) {
            if ($dayOfYear <= $length) {
                assert($month <= 12);
                assert($dayOfYear > 0);

                return [$month, $dayOfYear];
            }

            $dayOfYear -= $length;
            $month++;
        }

        throw new InvalidDayOfYear('Invalid dayOfYear: ' . $dayOfYear);
    }

    /**
     * Calculates the starting day of the week for a given year.
     *
     * @param int $year The year for which the starting day is to be calculated.
     * @return int The calculated starting day of the week for the specified year.
     */
    protected function yearStartDay(int $year): int
    {
        return 365 * $year
            + \intdiv($year, 4)
            - \intdiv($year, 100)
            + \intdiv($year, 400);
    }

    abstract protected static function epochDayOffset(): int;

    /**
     * Calculates the year, month, and day from the given epoch day.
     *
     * This method has converted the number of days since the epoch (0000-03-01, with an epoch of 1970-01-01) into
     * a corresponding year, month, and day. The calculation uses a March-based calendar to simplify leap year
     * handling and avoids division problems for negative epoch day values.
     *
     * @param int $epochDay The number of days since the epoch day (1970-01-01).
     *
     * @return array{0:int,1:int<1,12>,2:int<1,31>} Returns an array containing three integer values:
     *               [0] => The computed year.
     *               [1] => The computed month (1-based, 1 = January, ..., 12 = December).
     *               [2] => The computed day of the month.
     */
    public function dateFromEpochDay(int $epochDay): array
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

        return [
            $yearEst + $adjust + \intdiv($marchMonth0, 10),
            $month,
            $day,
        ];
    }

    public function epochDayFromDate(int $year, int $month, int $day): int
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
            if (! $this->isLeapYear($year)) {
                $total -= 1;
            }
        }

        return $total - static::epochDayOffset();
    }

    abstract public function isLeapYear(int $year): bool;

    abstract public function isValidDate(int $year, int $month, int $day): bool;

    /**
     * Converts a combination of years and months into the equivalent number of days
     * by adjusting the given date and calculating the difference in epoch days.
     *
     * @param int $year   The starting year of the date.
     * @param int $month  The starting month of the date.
     * @param int $day    The starting day of the date.
     * @param int $years  The number of years to add to the starting date.
     * @param int $months The number of months to add to the starting date.
     * @return int The number of days representing the difference between the adjusted date and the starting date.
     */
    public function yearsMonthsToDays(int $year, int $month, int $day, int $years, int $months): int
    {
        $totalMonthsDelta = $years * 12 + $months;
        [$newYear, $newMonth, $newDay] = $this->adjustDate($year + $years, $month + $months, $day);

        if ($newYear === 0) {
            $newYear = $totalMonthsDelta >= 0 ? 1 : -1;
        }

        return $this->epochDayFromDate($newYear, $newMonth, $newDay) - $this->epochDayFromDate($year, $month, $day);
    }

    /**
     * Adjusts the provided date values to ensure valid ranges for year, month, and day.
     *
     * @param int $year  The year to be adjusted.
     * @param int $month The month to be adjusted. May overflow (>12) or underflow (<1).
     * @param int $day   The day to be adjusted. Accounts for valid month lengths.
     *
     * @return array{0:int,1:int<1,12>,2:int<1,31>} An array containing the adjusted year, month, and day in the format
     */
    private function adjustDate(int $year, int $month, int $day): array
    {
        // Handle month overflow (months > 12 or < 1)
        while ($month > 12) {
            $month -= 12;
            $year++;
        }

        while ($month < 1) {
            $month += 12;
            $year--;
        }

        // Adjust days when subtracting more than exists in a month
        while ($day < 1) {
            $month--;
            if ($month < 1) {
                $month = 12;
                $year--;
            }

            $day += $this->monthLength($year, $month);
        }

        // Get maximum days for the corrected month/year
        $monthLength = $this->monthLength($year, $month);

        // Handling leap year transition specifically
        if ($month === 2 && $day === 29 && !$this->isLeapYear($year)) {
            $day = 28; // Adjust from Feb 29 to Feb 28 in non-leap years
        }

        if ($day > $monthLength) {
            $day = $monthLength;
        }

        assert($day <= $monthLength);

        return [$year, $month, $day];
    }
}
