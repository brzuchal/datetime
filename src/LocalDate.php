<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Format\DateTimeFormat;
use Brzuchal\DateTime\Format\DateTimeFormatter;
use Brzuchal\DateTime\Format\ParseException;
use Brzuchal\DateTime\Format\TemporalAccessor;
use Brzuchal\DateTime\Format\TemporalField;
use Brzuchal\DateTime\Format\UnsupportedPatternSymbol;

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
    public int $epochDay {
        get => self::calcToEpochDay($this->year, $this->month, $this->day);
    }

    /**
     * Indicates whether the current year is a leap year.
     */
    public bool $isLeapYear {
        get => self::isLeapYear($this->year);
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
     * @throws InvalidDate
     */
    public static function of(int $year, int $month, int $day): self
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidDate("Month must be 1-12, got {$month}");
        }

        $isLeap = self::isLeapYear($year);
        $monthLengths = self::monthLengths($isLeap);
        if ($day < 1 || $day > $monthLengths[$month - 1]) {
            throw new InvalidDate("Invalid day {$day} for date {$year}-{$month}-{$day}, must be 1-{$monthLengths[$month - 1]}");
        }

        return new self($year, $month, $day, self::calcToEpochDay($year, $month, $day));
    }

    public static function epoch(int $epochDay): self
    {
        [$year, $month, $day] = self::calcFromEpochDay($epochDay);

        return new self($year, $month, $day, $epochDay);
    }

    /**
     * Parses a date string (default "YYYY-MM-DD").
     * @throws InvalidDate
     * @throws ParseException
     * @throws Format\UnsupportedPatternSymbol
     */
    public static function parse(string $text, DateTimeFormatter|null $formatter = null): self
    {
        $formatter ??= DateTimeFormatter::of(DateTimeFormat::ExtendedIsoLocalDate);

        return self::from($formatter->parse($text));
    }

    /**
     * @throws ParseException
     * @throws InvalidDate
     * @throws InsufficientDateComponents
     */
    public static function from(TemporalAccessor $accessor): self
    {
        if (!($accessor instanceof self) && ! $accessor->has(TemporalField::Year, TemporalField::Month, TemporalField::Day)) {
            throw new InsufficientDateComponents('Insufficient fields for LocalDate');
        }

        return self::of(
            year: $accessor->get(TemporalField::Year),
            month: $accessor->get(TemporalField::Month),
            day: $accessor->get(TemporalField::Day),
        );
    }

    /**
     * Returns ISO 8601 Extended string "YYYY-MM-DD".
     * @throws UnsupportedPatternSymbol
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
        return self::epoch($this->epochDay + $days);
    }

    /**
     * Returns a new LocalDate subtracting $days from this date.
     */
    public function minusDays(int $days): self
    {
        return self::epoch($this->epochDay - $days);
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

    public function plusYears(int $years): self
    {
        return self::resolveAdjusted($this->year + $years, $this->month, $this->day);
    }

    public function plusMonths(int $months): self
    {
        $totalMonths = $this->year * 12 + ($this->month - 1) + $months;
        $newYear = intdiv($totalMonths, 12);
        $newMonth = ($totalMonths % 12) + 1;
        return self::resolveAdjusted($newYear, $newMonth, $this->day);
    }

    private static function resolveAdjusted(int $year, int $month, int $day): self
    {
        $monthLengths = LocalDate::MONTH_LENGTHS;
        if (LocalDate::isLeapYear($year)) {
            $monthLengths[1] = 29;
        }
        $maxDay = $monthLengths[$month - 1] ?? 28;
        $resolvedDay = min($day, $maxDay);
        return LocalDate::of($year, $month, $resolvedDay);
    }

    // Calculation methods for dates

    private function calculateDayOfWeek(): DayOfWeek
    {
        // 1970-01-01 was a Thursday => offset=3 => 0 => Monday, 6 => Sunday
        $dayOfWeekIndex = ($this->epochDay + 3) % 7;
        if ($dayOfWeekIndex < 0) {
            $dayOfWeekIndex += 7;
        }
        return DayOfWeek::from($dayOfWeekIndex);
    }

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

    protected static function monthLengths(bool $isLeap): array
    {
        $lengths = self::MONTH_LENGTHS;
        if ($isLeap) {
            $lengths[1] = 29;
        }

        return $lengths;
    }

    public static function isLeapYear(int $year): bool
    {
        return ($year % 400 === 0) || ($year % 4 === 0 && $year % 100 !== 0);
    }

    private static function calcToEpochDay(int $year, int $month, int $day): int
    {
        // Number of days from the beginning of year 0 to the beginning of year $y (excluding the current year)
        $total = 365 * $year;
        if ($year >= 0) {
            // For years >= 0 (year 0 = 1 CE) you can use directly:
            $total += intdiv($year + 3, 4) - intdiv($year + 99, 100) + intdiv($year + 399, 400);
        } else {
            // For negative years (proleptic, year 0 = 1 BC), we use integer division with floor rounding.
            $total += intdiv($year, 4) - intdiv($year, 100) + intdiv($year, 400);
        }

        // We are adding days of the current year based on the month and day.
        $total += intdiv(367 * $month - 362, 12) + $day - 1;
        if ($month > 2) {
            $total -= 1;
            if (! self::isLeapYear($year)) {
                $total -= 1;
            }
        }

        return $total - self::DAYS_0000_TO_1970;
    }

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
        $doyEst = $zeroDay - (365 * $yearEst + \intdiv($yearEst, 4) - \intdiv($yearEst, 100) + intdiv($yearEst, 400));
        if ($doyEst < 0) {
            // Correction of the year estimate if the day of the year is negative.
            $yearEst--;
            $doyEst = $zeroDay - (365 * $yearEst + \intdiv($yearEst, 4) - \intdiv($yearEst, 100) + intdiv($yearEst, 400));
        }

        // Conversion from the March calendar (Mar-1 as the beginning of the year) to the regular one (Jan-1)
        $marchDoy0 = (int) $doyEst;
        $marchMonth0 = \intdiv($marchDoy0 * 5 + 2, 153);

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

    public function __unserialize(array $data): void
    {
        [$year, $month, $day] = \explode('-', $data['date'], 3);
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
