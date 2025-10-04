<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\InvalidDate;
use Brzuchal\DateTime\LocalDate;

use function PHPUnit\Framework\assertFalse;

/**
 * A calendar system based on the proleptic Gregorian calendar used by the ISO-8601 standard.
 * This class provides functionality to calculate dates and epochs using a 400-year cycle
 * of the Gregorian calendar, handling leap years and epoch-based transformations.
 */
final class IsoCalendarSystem extends BaseGJCalendarSystem
{
    /**
     * Number of days between 0000-03-01 and 1970-01-01 in proleptic Gregorian,
     * so that epochDay=0 is 1970-01-01.
     *
     * DAYS_PER_CYCLE * 5: covers 2000 years using five full 400-year cycles
     * - (30 * 365): removes non-leap-year days from an extra 30 years
     * - 7: removes 7 leap years in those 30 years
     * - 59: adjust for the March 1 base, subtracting January & February
     */
    private const int DAYS_0000_TO_1970 = self::DAYS_PER_CYCLE * 5 - (30 * 365) - 7 - 59;

    /**
     * @var array<non-negative-int,Era>
     */
    protected static array $eras;

    public function __construct()
    {
        self::$eras ??= [
            new Era(0, 'BCE', 'Before Common Era', endEpochDay: -719162),
            new Era(1, 'CE', 'Common Era', startEpochDay: -719161),
        ];
    }

    public function name(): string
    {
        return 'ISO';
    }

    /**
     * Validates whether the given year, month, and day combination represents a valid calendar date.
     *
     * @param int $year  The year part of the date.
     * @param int $month The month part of the date (1-12).
     * @param int $day   The day part of the date (1-31, depending on the month and year).
     *
     * @return bool True if the provided date is valid; false otherwise.
     */
    public function isValidDate(int $year, int $month, int $day): bool
    {
        if ($month < 1 || $month > 12) {
            return false;
        }

        $monthLength = $this->monthLength($year, $month);

        return $day >= 1 && $day <= $monthLength;
    }

    /**
     * Determines if a given year is a leap year.
     *
     * @param int $year The year to check.
     *
     * @return bool True if the year is a leap year, false otherwise.
     */
    public function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0) && (($year % 100 !== 0) || ($year % 400 === 0));
    }

    /**
     * Retrieves the constant value representing the offset in days from the epoch day (1970-01-01)
     * to the baseline date (0000-03-01).
     *
     * @return int The offset in days from the epoch day to the baseline date.
     */
    protected static function epochDayOffset(): int
    {
        return self::DAYS_0000_TO_1970;
    }

    /**
     * Converts an Instant object to a LocalDate representation.
     *
     * @param Instant $instant The instant to be converted, representing a point in time.
     *
     * @return LocalDate The LocalDate representation of the given instant.
     *
     * @throws InvalidDate On an invalid date.
     */
    public function date(Instant $instant): LocalDate
    {
        [$year, $month, $day] = $this->dateFromEpochDay((int) ($instant->epochSecond / 86400));

        return LocalDate::of($year, $month, $day, $this);
    }

    /**
     * Converts an epoch day (days since 1970-01-01) to a date represented as a year, month, and day.
     *
     * @param int $epochDay The number of days since the epoch date (1970-01-01).
     *
     * @return array{0:int,1:int<1,12>,2:int<1,31>} An associative array containing the calculated date elements:
     *               - `0`: The year part of the date.
     *               - `1`: The month part of the date (1-12).
     *               - `2`: The day part of the date (1-31).
     */
    public function dateFromEpochDay(int $epochDay): array
    {
        // The offset: number of days from 0000-03-01 to 1970-01-01 and subtract that day
        $offset = self::DAYS_0000_TO_1970 - 1;

        // n is the number of days from 0000-03-01 for the given epochDay.
        $n = $epochDay + $offset;

        // Compute the "era": number of 400-year cycles.
        // Note: 146097 is the number of days in 400 years.
        if ($n >= 0) {
            $era = \intdiv($n, self::DAYS_PER_CYCLE);
        } else {
            // Adjust for negatives – ensures correct floor division.
            $era = \intdiv($n - self::DAYS_PER_CYCLE + 1, self::DAYS_PER_CYCLE);
        }

        // Day of the era: remainder within the current 400-year cycle.
        $doe = $n - $era * 146097; // 0 <= doe <= 146096

        // Year-of-era: [0, 399]
        $yoe = \intdiv(
            $doe
            - \intdiv($doe, 1460)    // accounts for 4-year cycles
            + \intdiv($doe, 36524)   // subtract century offsets
            - \intdiv($doe, self::DAYS_PER_CYCLE + 1),  // add back 400-year corrections
            365,
        );

        // The actual year is:
        $year = $yoe + $era * 400;

        // Day of year: remainder within the year, 0-indexed.
        $doy = $doe - (365 * $yoe + \intdiv($yoe, 4) - \intdiv($yoe, 100));

        // Month part:
        // (5*t + 2) / 153 will give a value in range 0 to 11 which we convert to the proper month.
        $mp = \intdiv(5 * $doy + 2, 153);

        // Day of the month: convert from "day of era" value.
        $day = $doy - \intdiv(153 * $mp + 2, 5) + 1;
        assert($day >= 1 && $day <= 31);

        // Month: convert mp to [1, 12]. If mp < 10, then month = mp + 3, otherwise month = mp - 9.
        $month = $mp < 10 ? $mp + 3 : $mp - 9;
        assert($month >= 1 && $month <= 12);

        // If the month is January or February, they belong to the previous calendar year.
        if ($month <= 2) {
            $year++;
        }

        return [$year, $month, $day];
    }

    /**
     * Calculates the number of days from the epoch day (1970-01-01) to the provided date.
     *
     * @param int $year  The year part of the date.
     * @param int<1,12> $month The month part of the date.
     * @param int<1,31> $day   The day part of the date.
     *
     * @return int The calculated epoch day corresponding to the provided date.
     */
    public function epochDayFromDate(int $year, int $month, int $day): int
    {
        // Calculate days from 0000-03-01 to the 1st January of a given year
        $total = $this->yearStartDay($year);
        $daysToAdd = 0;

        for ($i = 1; $i < $month; $i++) {
            $daysToAdd += $this->monthLength($year, $i);
        }

        // Add calculated days and day value
        $total += $daysToAdd + $day;

        // Subtract days from 0000-03-01 to 1970-01-01
        return $total - self::DAYS_0000_TO_1970;
    }

    /**
     * March0 based year offset
     */
    protected function yearStartDay(int $year): int
    {
        return \intdiv($year, 400) * self::DAYS_PER_CYCLE // calculate how many full 400-year cycles fit
            + ($year % 400) * 365 // each remaining non-leap year adds 265
            + \intdiv($year % 400, 4) // adds one extra day for every 4 years that are not leap
            - \intdiv($year % 400, 100) // removes century years that are not leap years
            + \intdiv($year % 400, 400) // adds back leap centuries (every 400 years is a leap)
            - ($this->isLeapYear($year) ? 60 : 59); // Adjust for a March-based system
    }

    /**
     * Determines the era (e.g., BCE or CE) for the given date based on the year.
     *
     * @param int $year  The year part of the date.
     * @param int $month The month part of the date (1-12).
     * @param int $day   The day part of the date (1-31).
     *
     * @return Era The era corresponding to the provided year.
     */
    public function eraOf(int $year, int $month, int $day): Era
    {
        return self::$eras[$year <= 0 ? 0 : 1];
    }
}
