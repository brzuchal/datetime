<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Clock\SystemClock;
use Brzuchal\DateTime\Format\DateTimeFormat;
use Brzuchal\DateTime\Format\DateTimeFormatter;
use Brzuchal\DateTime\Format\InvalidInput;
use Brzuchal\DateTime\Format\InvalidPattern;
use Brzuchal\DateTime\Format\TemporalAccessor;
use Brzuchal\DateTime\Format\TemporalField;
use Brzuchal\DateTime\Format\UnsupportedPatternSymbol;

/**
 * Immutable representation of a calendar date without time or timezone.
 *
 * Represents a date in ISO-8601 calendar system, such as 2025-03-17.
 * Does not contain any time-of-day or timezone information.
 *
 * Example usage:
 *   $date = LocalDate::of(2025, 3, 17);
 *   $today = LocalDate::now();
 *   $tomorrow = $today->plusDays(1);
 */
final class LocalDate implements TemporalAccessor
{
    /**
     * The total number of days in a 400-year cycle of the proleptic Gregorian calendar.
     */
    private const int DAYS_PER_CYCLE = 146097;

    /**
     * Number of days between 0000-03-01 and 1970-01-01 in proleptic Gregorian,
     * so that epochDay=0 is 1970-01-01.
     */
    private const int DAYS_0000_TO_1970 = self::DAYS_PER_CYCLE * 5 - (30 * 365 + 7);

    /**
     * Array representing the number of days in each month of a common year,
     * where the index corresponds to the month's position in the year (January = 0).
     */
    public const array MONTH_LENGTHS = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    /**
     * @param int       $year  Represents a specific calendar year in the proleptic Gregorian calendar system.
     * @param int<1,12> $month Represents a month
     * @param int<1,31> $day   Represents a day.
     */
    private function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $day,
    ) {
    }

    // Hooked properties to reduce calculations on dates

    /**
     * Represents a number of days from the beginning of year 0
     */
    private(set) int $epochDay {
        get => $this->epochDay ??= self::calcToEpochDay($this->year, $this->month, $this->day);
        set => $value;
    }

    /**
     * Indicates whether the current year is a leap year.
     */
    private(set) bool $isLeapYear {
        get => $this->isLeapYear ??= self::isLeapYear($this->year);
        set => $value;
    }

    /**
     * Represents the day of the week.
     */
    public DayOfWeek $dayOfWeek {
        get => $this->calculateDayOfWeek();
    }

    /**
     * The day of the year, typically ranging from 1 to 365, or 1 to 366 in a leap year.
     */
    public int $dayOfYear {
        get => $this->calculateDayOfYear();
    }

    /**
     * Calculates the ISO week number of the year for the current date.
     * The first week of the year is defined as the one that contains January 4th,
     * and weeks start on a Monday.
     */
    public int $weekOfYear {
        get => \intdiv($this->dayOfYear - $this->dayOfWeek->value + 10, 7);
    }

    /**
     * Calculates the week of the month for the current date.
     * The value is determined by dividing the adjusted day of the month
     * (offset by the day of the week and 10) by 7.
     */
    public int $weekOfMonth {
        get => \intdiv($this->day - $this->dayOfWeek->value + 10, 7);
    }

    /**
     * Creates a LocalDate from year-month-day.
     *
     * @throws InvalidDate
     */
    public static function of(int $year, int $month, int $day): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidDate('Month must be 1-12, got ' . $month);
        }

        $isLeapYear = self::isLeapYear($year);
        $monthLengths = self::monthLengths($isLeapYear);
        if ($day < 1 || $day > $monthLengths[$month - 1]) {
            throw new InvalidDate(\sprintf(
                'Invalid day %d for date %d-%d-%d, must be 1-%d',
                $day, $year, $month, $day, $monthLengths[$month - 1],
            ));
        }

        $date = new self($year, $month, $day);
        $date->isLeapYear = $isLeapYear;

        return $date;
    }

    /**
     * Creates an instance of the class from the specified epoch day.
     * The epoch day is the number of days since 1970-01-01 (ISO calendar system).
     *
     * @param int $epochDay The number of days since the epoch of 1970-01-01.
     *
     * @return self An instance representing the date corresponding to the given epoch day.
     */
    public static function fromEpochDay(int $epochDay): self
    {
        [$year, $month, $day] = self::calcFromEpochDay($epochDay);

        $date = new self($year, $month, $day);
        $date->epochDay = $epochDay;

        return $date;
    }

    /**
     * Parses a date string and returns an instance of the class.
     *
     * @param string $text The date string to be parsed.
     * @param DateTimeFormatter|null $formatter Optional formatter to define the parsing rules. Defaults to Extended ISO Local Date format.
     *
     * @return self An instance of the class representing the parsed date.
     *
     * @throws InvalidPattern If formatter pattern is incorrect.
     * @throws InsufficientDateComponents If parse does not provide all necessary information about the input date.
     * @throws InvalidDate If there is no valid conversion possible.
     * @throws InvalidInput If any error occurs during parsing.
     * @throws UnsupportedPatternSymbol If formatter pattern provides unsupported symbols
     */
    public static function parse(string $text, DateTimeFormatter|null $formatter = null): self
    {
        $formatter ??= DateTimeFormatter::of(DateTimeFormat::ExtendedIsoLocalDate);

        return self::from($formatter->parse($text));
    }

    /**
     * Creates an instance of the class from the given TemporalAccessor.
     * The TemporalAccessor must contain the fields Year, Month, and Day.
     *
     * @param TemporalAccessor $accessor The TemporalAccessor to convert.
     *
     * @return self An instance of the class.
     *
     * @throws InsufficientDateComponents If the TemporalAccessor does not have sufficient fields.
     * @throws InvalidDate If there is no valid conversion possible.
     */
    public static function from(TemporalAccessor $accessor): self
    {
        $year = $accessor->get(TemporalField::Year);
        $month = $accessor->get(TemporalField::Month);
        $day = $accessor->get(TemporalField::Day);
        if ($year === null || $month === null || $day === null) {
            throw new InsufficientDateComponents('Insufficient fields for LocalDate');
        }

        return self::of(year: $year, month: $month, day: $day);
    }

    /**
     * Returns ISO 8601 Extended string "YYYY-MM-DD".
     *
     * @throws UnsupportedPatternSymbol If formatter pattern provides unsupported symbols
     * @throws InvalidPattern If formatter pattern is incorrect.
     */
    public function __toString(): string
    {
        return DateTimeFormatter::of(DateTimeFormat::ExtendedIsoLocalDate)->format($this);
    }

    // Mutators

    /**
     * Returns a new LocalDate adding $days to this date.
     */
    public function plusDays(int $days): self
    {
        return self::fromEpochDay($this->epochDay + $days);
    }

    /**
     * Returns a new LocalDate subtracting $days from this date.
     */
    public function minusDays(int $days): self
    {
        return self::fromEpochDay($this->epochDay - $days);
    }

    public function plus(Period $period): self
    {
        return $this
            ->plusYears($period->years)
            ->plusMonths($period->months)
            ->plusDays($period->days);
    }

    public function minus(Period $period): self
    {
        return $this->plus($period->negate());
    }

    /**
     * Returns a copy of this instance with the specified number of years added.
     *
     * @param int $years The number of years to add, may be negative to subtract.
     *
     * @return self A new instance with the years added.
     *
     * @throws InvalidDate If there is no valid conversion.
     */
    public function plusYears(int $years): self
    {
        return self::resolveAdjusted($this->year + $years, $this->month, $this->day);
    }

    /**
     * Returns a new instance with the specified number of months added.
     *
     * @param int $months The number of months to add, may be negative to subtract months.
     *
     * @return self A new instance adjusted by the specified number of months.
     *
     * @throws InvalidDate If there is no valid conversion.
     */
    public function plusMonths(int $months): self
    {
        $totalMonths = $this->year * 12 + ($this->month - 1) + $months;
        $newYear = \intdiv($totalMonths, 12);
        $newMonth = ($totalMonths % 12) + 1;

        return self::resolveAdjusted($newYear, $newMonth, $this->day);
    }

    /**
     * Resolves and adjusts the provided year, month, and day to a valid date, considering leap years and month boundaries.
     *
     * @param int $year The year to resolve.
     * @param int $month The month to resolve (1-12).
     * @param int $day The day to resolve (1-31). This value will be adjusted to the maximum valid day for the specified month and year.
     *
     * @return self The resolved LocalDate instance.
     *
     * @throws InvalidDate If there is no valid conversion.
     */
    private static function resolveAdjusted(int $year, int $month, int $day): self
    {
        $isLeapYear = self::isLeapYear($year);
        $monthLengths = self::monthLengths($isLeapYear);
        $maxDay = $monthLengths[$month - 1] ?? 28;
        $resolvedDay = \min($day, $maxDay);

        $date = LocalDate::of($year, $month, $resolvedDay);
        $date->isLeapYear = $isLeapYear;

        return $date;
    }

    // Calculation methods for dates

    /**
     * Calculates the day of the week based on the epoch day.
     *
     * @return DayOfWeek The day of the week corresponding to the calculated index, where 0 represents Monday and 6 represents Sunday.
     */
    private function calculateDayOfWeek(): DayOfWeek
    {
        // 1970-01-01 was a Thursday => offset=3 => 0 => Monday, 6 => Sunday
        $dayOfWeekIndex = ($this->epochDay + 3) % 7;
        if ($dayOfWeekIndex < 0) {
            $dayOfWeekIndex += 7;
        }

        return DayOfWeek::from($dayOfWeekIndex);
    }

    /**
     * Calculates the day of the year for the current date instance (start from 1).
     *
     * @return int The calculated day of the year (1-365 for common years, 1-366 for leap years).
     */
    private function calculateDayOfYear(): int
    {
        // Day of year calculation (1-365/366)
        $monthLengths = self::monthLengths($this->isLeapYear);
        $doy = $this->day;
        for ($m = 1; $m < $this->month; $m++) {
            $doy += $monthLengths[$m - 1];
        }

        return $doy;
    }

    /**
     * Returns an array of month lengths for a standard year or a leap year.
     *
     * @param bool $isLeap Indicates whether the year is a leap year (true) or not (false).
     *
     * @return array{0:31,1:int<28,29>,2:31,3:30,4:31,5:30,6:31,7:31,8:30,9:31,10:30,11:31} An array containing the lengths of each month.
     */
    protected static function monthLengths(bool $isLeap): array
    {
        $lengths = self::MONTH_LENGTHS;
        if ($isLeap) {
            $lengths[1] = 29;
        }

        return $lengths;
    }

    /**
     * Determines if a given year is a leap year.
     *
     * @param int $year The year to check.
     *
     * @return bool True if the year is a leap year, false otherwise.
     */
    public static function isLeapYear(int $year): bool
    {
        return ($year % 400 === 0) || ($year % 4 === 0 && $year % 100 !== 0);
    }

    /**
     * Calculates the number of days from the epoch day (1970-01-01) to the provided date.
     *
     * @param int $year The year part of the date.
     * @param int $month The month part of the date (1-12).
     * @param int $day The day part of the date (1-31).
     *
     * @return int The calculated epoch day corresponding to the provided date.
     */
    private static function calcToEpochDay(int $year, int $month, int $day): int
    {
        // Number of days from the beginning of year 0 to the beginning of year $y (excluding the current year)
        $total = 365 * $year;
        if ($year >= 0) {
            // For years >= 0 (year 0 = 1 CE) you can use directly:
            $total += \intdiv($year + 3, 4) - \intdiv($year + 99, 100) + \intdiv($year + 399, 400);
        } else {
            // For negative years (proleptic, year 0 = 1 BC), we use integer division with floor rounding.
            $total += \intdiv($year, 4) - \intdiv($year, 100) + \intdiv($year, 400);
        }

        // We are adding days of the current year based on the month and day.
        $total += \intdiv(367 * $month - 362, 12) + $day - 1;
        if ($month > 2) {
            $total -= 1;
            if (! self::isLeapYear($year)) {
                $total -= 1;
            }
        }

        return $total - self::DAYS_0000_TO_1970;
    }

    /**
     * Calculates the year, month, and day from the given epoch day.
     *
     * This method converts the number of days since the epoch (0000-03-01, with an epoch of 1970-01-01) into
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
    private static function calcFromEpochDay(int $epochDay): array
    {
        $zeroDay = $epochDay + self::DAYS_0000_TO_1970;
        // Shifting the beginning of the year to 0000-03-01 (March reference system for easier calculation of leap years)
        $zeroDay -= 60;  // 0 is now responding to the date 0000-03-01
        $adjust = 0;
        if ($zeroDay < 0) {
            // For negative zeroDay values, we perform a correction to avoid division problems (ensuring a non-negative value for calculations).
            $adjustCycles = \intdiv($zeroDay + 1, self::DAYS_PER_CYCLE) - 1;
            $adjust = $adjustCycles * 400;
            $zeroDay -= $adjustCycles * self::DAYS_PER_CYCLE;
        }

        // Approximate year calculation based on the approximate day number in the 400-year cycle
        $yearEst = \intdiv(400 * $zeroDay + 591, self::DAYS_PER_CYCLE);
        // Calculating the day of the year (doyEst) for the estimated year
        $doyEst = $zeroDay - (365 * $yearEst + \intdiv($yearEst, 4) - \intdiv($yearEst, 100) + \intdiv($yearEst, 400));
        if ($doyEst < 0) {
            // Correction of the year estimate if the day of the year is negative.
            $yearEst--;
            $doyEst = $zeroDay - (365 * $yearEst + \intdiv($yearEst, 4) - \intdiv($yearEst, 100) + \intdiv($yearEst, 400));
        }

        // Conversion from the March calendar (Mar-1 as the beginning of the year) to the regular one (Jan-1)
        $marchDoy0 = (int) $doyEst;
        $marchMonth0 = \intdiv($marchDoy0 * 5 + 2, 153);

        /** @phpstan-ignore return.type */
        return [
            $yearEst + $adjust + \intdiv($marchMonth0, 10),
            ($marchMonth0 + 2) % 12 + 1,
            $marchDoy0 - \intdiv($marchMonth0 * 306 + 5, 10) + 1,
        ];
    }

    // Serialization

    public function __serialize(): array
    {
        return ['date' => (string) $this];
    }

    /**
     * @param array{date:non-empty-string} $data
     */
    public function __unserialize(array $data): void
    {
        [$year, $month, $day] = \explode('-', $data['date'], 3);
        /** @phpstan-ignore argument.type */
        self::__construct(
            year: (int) $year,
            month: (int) $month,
            day: (int) $day,
        );
    }

    // TemporalAccessor

    public function get(TemporalField $field): int|null
    {
        return match ($field) {
            TemporalField::Year => $this->year,
            TemporalField::Month => $this->month,
            TemporalField::Day => $this->day,
            TemporalField::DayOfYear => $this->dayOfYear,
            TemporalField::DayOfWeek => $this->dayOfWeek->value,
            default => null,
        };
    }

    public function has(TemporalField... $fields): bool
    {
        foreach ($fields as $field) {
            if (match ($field) {
                TemporalField::Year, TemporalField::Month, TemporalField::Day, TemporalField::DayOfYear, TemporalField::DayOfWeek => true,
                default => false,
            }) {
                continue;
            }

            return false;
        }

        return true;
    }
}
