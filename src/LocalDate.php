<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\CalendarSystems\CalendarSystem;
use Brzuchal\DateTime\CalendarSystems\CalendarSystemRegistry;
use Brzuchal\DateTime\CalendarSystems\Era;
use Brzuchal\DateTime\CalendarSystems\IsoCalendarSystem;
use Brzuchal\DateTime\CalendarSystems\UnknownCalendarSystem;
use Brzuchal\DateTime\Format\DateTimeFormat;
use Brzuchal\DateTime\Format\DateTimeFormatter;
use Brzuchal\DateTime\Format\InvalidInput;
use Brzuchal\DateTime\Format\InvalidPattern;
use Brzuchal\DateTime\Format\UnsupportedPatternSymbol;
use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;

/**
 * Immutable representation of a calendar date without time or timezone.
 *
 * Represents a date in the ISO-8601 calendar system, such as 2025-03-17.
 * Does not contain any time-of-day or timezone information.
 *
 * Example usage:
 * ```php
 *   $date = LocalDate::of(2025, 3, 17);
 *   $today = LocalDate::now();
 *   $tomorrow = $today->plusDays(1);
 * ```
 */
final class LocalDate implements TemporalAccessor
{
    private const int NANOS_PER_SECOND = 1_000_000_000;
    private const int NANOS_PER_DAY = 86_400 * self::NANOS_PER_SECOND;

    /**
     * @param int          $year  Represents a specific calendar year in the proleptic Gregorian calendar system.
     * @param positive-int $month Represents a month
     * @param positive-int $day   Represents a day.
     */
    private function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $day,
        public readonly CalendarSystem $calendar,
    ) {
    }

    // Hooked properties to reduce date calculations

    /**
     * Represents a number of days from the beginning of year 0
     */
    private(set) int $epochDay {
        get => $this->epochDay ??= $this->calendar->epochDayFromDate($this->year, $this->month, $this->day);
    }

    /**
     * Indicates whether the current year is a leap year.
     */
    private(set) bool $isLeapYear {
        get => $this->isLeapYear ??= $this->calendar->isLeapYear($this->year);
    }

    /**
     * Represents the day of the week.
     */
    public int $dayOfWeek {
        get => $this->calculateDayOfWeek();
    }

    /**
     * The day of the year, typically ranging from 1 to 365, or 1 to 366 in a leap year.
     */
    public int $dayOfYear {
        get => $this->calendar->dayOfYear($this->year, $this->month, $this->day);
    }

    /**
     * Calculates the ISO week number of the year for the current date.
     * The first week of the year is defined as the one that contains January 4th,
     * and weeks start on a Monday.
     */
    public int $weekOfYear {
        get => $this->calculateWeekOfYear();
    }

    /**
     * Calculates the week of the month for the current date.
     * The value is determined by counting ISO weeks starting on Monday within the month.
     */
    public int $weekOfMonth {
        get => $this->calculateWeekOfMonth();
    }

    public Era $era {
        get => $this->calendar->eraOf($this->year, $this->month, $this->day);
    }

    /**
     * Creates a new instance from year-month-day.
     *
     * This method allows creating a date aligned with a given {@see CalendarSystem}. By default,
     * it uses the {@see IsoCalendarSystem}, but you can optionally provide a custom one.
     *
     * Basic example:
     * ```php
     * $date = LocalDate::of(2024, 2, 29);
     * // Creates February 29th, 2024 in the ISO calendar system.
     * ```
     *
     * Advanced usage with a custom calendar system:
     * ```php
     * $customCalendar = new MyCustomCalendarSystem();
     * $date = LocalDate::of(2024, 2, 29, $customCalendar);
     * // Creates a date within a custom calendar system.
     * // Throws InvalidDate if the date isn't valid in that system.
     * ```
     *
     * Handling invalid dates:
     * ```php
     * try {
     *     $date = LocalDate::of(2024, 2, 29);
     * } catch (InvalidDate $e) {
     *     // February 29th, 2024 is invalid for the given calendar,
     *     // so an exception is thrown.
     *     echo $e->getMessage();
     * }
     * ```
     *
     * @param int            $year           Year component.
     * @param positive-int   $month          Month of the year (1–12 for the ISO calendar).
     * @param positive-int   $day            Day of the month (1–31 depending on the calendar system).
     * @param CalendarSystem $calendarSystem Calendar system used to validate the date; defaults to {@see IsoCalendarSystem}.
     *
     * @throws InvalidDate When the date is not valid for the provided {@see CalendarSystem}.
     */
    public static function of(
        int $year,
        int $month,
        int $day,
        CalendarSystem $calendarSystem = new IsoCalendarSystem(),
    ): self {
        if (!$calendarSystem->isValidDate($year, $month, $day)) {
            throw new InvalidDate(\sprintf('Invalid date provided: %s-%s-%s.', $year, $month, $day));
        }

        return new self($year, $month, $day, $calendarSystem);
    }

    /**
     * Creates an instance of the class from the specified epoch day.
     * The epoch day is the number of days since 1970-01-01 (ISO calendar system).
     *
     * @param int $epochDay       The number of days since the epoch of 1970-01-01.
     *
     * @param CalendarSystem $calendarSystem Calendar system used to interpret the epoch day; defaults to {@see IsoCalendarSystem}.
     *
     * @return self An instance representing the date corresponding to the given epoch day.
     */
    public static function fromEpochDay(int $epochDay, CalendarSystem $calendarSystem = new IsoCalendarSystem()): self
    {
        [$year, $month, $day] = $calendarSystem->dateFromEpochDay($epochDay);

        $date = new self($year, $month, $day, $calendarSystem);
        $date->epochDay = $epochDay;

        return $date;
    }

    /**
     * Parses a date string and returns an instance of the class.
     *
     * @param string $text      The date string to be parsed.
     * @param DateTimeFormatter|null $formatter Optional formatter to define the parsing rules. Defaults to Extended ISO Local Date format.
     *
     * @return self An instance of the class representing the parsed date.
     *
     * @throws InvalidPattern If the formatter pattern is incorrect.
     * @throws InsufficientDateComponents If parse does not provide all necessary information about the input date.
     * @throws InvalidDate If there is no valid conversion possible.
     * @throws InvalidInput If any error occurs during parsing.
     * @throws UnsupportedPatternSymbol If formatter pattern provides unsupported symbols.
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
        if (! $accessor->supports(TemporalField::Year, TemporalField::Month, TemporalField::Day)) {
            throw new InsufficientDateComponents(\sprintf('Insufficient fields for %s', self::class));
        }

        $year = $accessor->get(TemporalField::Year);
        $month = $accessor->get(TemporalField::Month);
        $day = $accessor->get(TemporalField::Day);
        assert($year !== null);
        assert($month > 0);
        assert($day > 0);

        return self::of(year: $year, month: $month, day: $day);
    }

    /**
     * Returns ISO 8601 Extended string "YYYY-MM-DD".
     *
     * @throws UnsupportedPatternSymbol If formatter pattern provides unsupported symbols.
     * @throws InvalidPattern If the formatter pattern is incorrect.
     */
    public function __toString(): string
    {
        return DateTimeFormatter::of(DateTimeFormat::ExtendedIsoLocalDate)->format($this);
    }

    // Mutators

    /**
     * Returns a date shifted forward by the specified offsets.
     *
     * @param int $years  Number of years to add.
     * @param int $months Number of months to add.
     * @param int $days   Number of days to add.
     *
     * @return self Updated date instance.
     */
    public function plus(int $years = 0, int $months = 0, int $days = 0): self
    {
        return $this->add(new Duration($years, $months, $days));
    }

    /**
     * Returns a date shifted backward by the specified offsets.
     *
     * @param int $years  Number of years to subtract.
     * @param int $months Number of months to subtract.
     * @param int $days   Number of days to subtract.
     *
     * @return self Updated date instance.
     */
    public function minus(int $years = 0, int $months = 0, int $days = 0): self
    {
        return $this->subtract(new Duration($years, $months, $days));
    }

    /**
     * Adds a duration to the current date.
     *
     * @param Duration $duration Duration composed of years, months, and days.
     *
     * @return self Adjusted date instance.
     */
    public function add(Duration $duration): self
    {
        $daysDelta = $duration->days
            + $this->calendar->yearsMonthsToDays(
                year: $this->year,
                month: $this->month,
                day: $this->day,
                years: $duration->years,
                months: $duration->months,
            )
            + self::timeDays($duration);

        return self::fromEpochDay($this->epochDay + $daysDelta, $this->calendar);
    }

    /**
     * Subtracts a duration from the current date.
     *
     * @param Duration $duration Duration composed of years, months, and days.
     *
     * @return self Adjusted date instance.
     */
    public function subtract(Duration $duration): self
    {
        $daysDelta = $duration->days
            + $this->calendar->yearsMonthsToDays(
                $this->year,
                $this->month,
                $this->day,
                -$duration->years,
                -$duration->months,
            )
            + self::timeDays($duration);

        return self::fromEpochDay($this->epochDay - $daysDelta, $this->calendar);
    }

    // Calculation methods for dates

    /**
     * Calculates the day of the week based on the epoch day.
     *
     * @return int The day of the week corresponding to the calculated index, where 0 represents Monday, and 6 represents Sunday.
     */
    private function calculateDayOfWeek(): int
    {
        // 1970-01-01 was a Thursday => offset=3 => 0 => Monday, 6 => Sunday
        $dayOfWeekIndex = ($this->epochDay + 3) % 7;
        if ($dayOfWeekIndex < 0) {
            $dayOfWeekIndex += 7;
        }

        return $dayOfWeekIndex;
    }

    // Serialization

    /**
     * @return array{date: non-empty-string, calendar: non-empty-string}
     *
     * @ignore
     */
    public function __serialize(): array
    {
        $date = (string) $this;
        assert($date !== '');

        return [
            'date' => $date,
            'calendar' => $this->calendar->name(),
        ];
    }

    /**
     * @param array{date: non-empty-string, calendar: non-empty-string} $data
     *
     * @throws UnknownCalendarSystem When the calendar system cannot be resolved.
     * @ignore
     */
    public function __unserialize(array $data): void
    {
        $parts = \explode('-', $data['date'], 3);
        assert(\count($parts) === 3);
        [$year, $month, $day] = $parts;
        assert(\is_numeric($year));
        assert(\is_numeric($month) && (int) $month > 0);
        assert(\is_numeric($day) && (int) $day > 0);
        $calendar = CalendarSystemRegistry::get($data['calendar']);

        self::__construct(
            year: (int) $year,
            month: (int) $month,
            day: (int) $day,
            calendar: $calendar,
        );
    }

    // TemporalAccessor

    /**
     * Returns the value for a requested temporal field if supported.
     *
     * @param TemporalField $field Temporal field to resolve.
     *
     * @return int|null Field value or null when not available.
     */
    public function get(TemporalField $field): int|null
    {
        return match ($field) {
            TemporalField::Era => $this->era->ordinal,
            TemporalField::Year => $this->year,
            TemporalField::Month => $this->month,
            TemporalField::Day => $this->day,
            TemporalField::DayOfYear => $this->dayOfYear,
            TemporalField::DayOfWeek => $this->dayOfWeek,
            default => null,
        };
    }

    /**
     * Verifies whether all provided temporal fields are available on this instance.
     *
     * @param TemporalField ...$fields Fields to validate.
     *
     * @return bool True when every field is supported.
     */
    public function supports(TemporalField ...$fields): bool
    {
        foreach ($fields as $field) {
            if (
                match ($field) {
                    TemporalField::Era,
                    TemporalField::Year,
                    TemporalField::Month,
                    TemporalField::Day,
                    TemporalField::DayOfYear,
                    TemporalField::DayOfWeek => true,
                    default => false,
                }
            ) {
                continue;
            }

            return false;
        }

        return true;
    }

    private static function timeDays(Duration $duration): int
    {
        $totalSeconds = ($duration->hours * 3600)
            + ($duration->minutes * 60)
            + $duration->seconds;

        $totalNanos = $totalSeconds * self::NANOS_PER_SECOND + $duration->nanos;

        if ($totalNanos === 0) {
            return 0;
        }

        return intdiv($totalNanos, self::NANOS_PER_DAY);
    }

    private function calculateWeekOfYear(): int
    {
        $dayOfWeek = $this->dayOfWeek + 1; // convert to 1 (Mon) - 7 (Sun)
        $week = intdiv($this->dayOfYear - $dayOfWeek + 10, 7);

        if ($week < 1) {
            return $this->weeksInWeekBasedYear($this->year - 1);
        }

        $weeksInYear = $this->weeksInWeekBasedYear($this->year);
        if ($week > $weeksInYear) {
            return 1;
        }

        return $week;
    }

    private function calculateWeekOfMonth(): int
    {
        $currentWeekStart = $this->epochDay - $this->dayOfWeek;
        $firstOfMonth = self::of($this->year, $this->month, 1, $this->calendar);
        $firstWeekStart = $firstOfMonth->epochDay - $firstOfMonth->dayOfWeek;

        return intdiv($currentWeekStart - $firstWeekStart, 7) + 1;
    }

    private function weeksInWeekBasedYear(int $year): int
    {
        $start = $this->weekYearStartEpochDay($year);
        $next = $this->weekYearStartEpochDay($year + 1);

        return intdiv($next - $start, 7);
    }

    private function weekYearStartEpochDay(int $year): int
    {
        $jan4Epoch = $this->calendar->epochDayFromDate($year, 1, 4);
        $jan4 = self::fromEpochDay($jan4Epoch, $this->calendar);

        return $jan4->epochDay - $jan4->dayOfWeek;
    }
}
