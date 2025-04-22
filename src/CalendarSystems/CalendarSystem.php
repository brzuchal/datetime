<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

interface CalendarSystem
{
    public function name(): string;

    /**
     * Converts the provided number of epoch days into a date array.
     *
     * @param int $epochDay The number of days since the Unix epoch (January 1, 1970).
     * @return array{0:int,1:int,2:int} An associative array containing date components, such as year, month, and day.
     */
    public function dateFromEpochDay(int $epochDay): array;

    /**
     * Calculates the epoch day number from the provided date.
     *
     * @param int $year The year component of the date.
     * @param int $month The month component of the date.
     * @param int $day The day component of the date.
     *
     * @return int The epoch day number corresponding to the input date.
     */
    public function epochDayFromDate(int $year, int $month, int $day): int;

    /**
     * Determines whether the specified year is a leap year.
     *
     * @param int $year The year to be checked.
     *
     * @return bool True if the year is a leap year, otherwise false.
     */
    public function isLeapYear(int $year): bool;

    public function isValidDate(int $year, int $month, int $day): bool;

    public function eraOf(int $year, int $month, int $day): Era;

    /**
     * Retrieves the number of months in a given year.
     *
     * @param int $year The year for which the number of months is determined.
     * @return int The number of months in the specified year.
     */
    public function monthsInYear(int $year): int;

    /**
     * Determines the number of days in a specific month of a given year.
     *
     * @param int $year The year to consider for determining the month's length, accounting for leap years if applicable.
     * @param int $month The month for which the number of days is determined, represented as an integer.
     * @return int The number of days in the specified month and year.
     */
    public function monthLength(int $year, int $month): int;

    /**
     * Calculates the number of days in a given year.
     *
     * @param int $year The year for which the number of days is determined.
     * @return int The total number of days in the specified year.
     */
    public function yearLength(int $year): int;

    /**
     * Calculates the day number within a given year for a specified date.
     *
     * @param int $year The year of the date.
     * @param int $month The month of the date.
     * @param int $day The day of the date.
     * @return int The day number within the year for the given date.
     */
    public function dayOfYear(int $year, int $month, int $day): int;

    /**
     * Calculates the number of days corresponding to a given number of years and months,
     * adjusting for leap years, month boundaries, and calendar-specific rules.
     *
     * @param int $year The starting year.
     * @param int $month The starting month.
     * @param int $day The starting day.
     * @param int $years The number of years to add or subtract.
     * @param int $months The number of months to add or subtract.
     * @return int The total number of days corresponding to the given offset.
     */
    public function yearsMonthsToDays(int $year, int $month, int $day, int $years, int $months): int;
}
