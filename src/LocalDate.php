<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\CalendarSystems\IsoCalendar;
use Brzuchal\DateTime\CalendarSystems\IsoEra;
use Brzuchal\DateTime\Format\DateTimeFormat;
use Brzuchal\DateTime\Format\DateTimeFormatter;
use Brzuchal\DateTime\Format\InvalidInput;
use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;

/**
 * Immutable representation of a calendar date without time or timezone.
 *
 * Represents a date in the ISO-8601 calendar system, such as 2025-03-17.
 * Does not contain any time-of-day or timezone information.
 *
 * Example usage:
 * <code>
 * $date = LocalDate::of(2025, 3, 17);
 * $today = LocalDate::now();
 * $tomorrow = $today->plusDays(1);
 * </code>
 */
final class LocalDate implements TemporalAccessor
{
    public const string SERIALIZED_DATE_FORMAT = '%04d-%02d-%02d';

    /**
     * @param int $year  Represents a specific calendar year in the proleptic Gregorian calendar system.
     * @param int<1, 12> $month Represents a month
     * @param int<1, 31> $day   Represents a day.
     */
    private function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $day,
    ) {
    }

    // Hooked properties to reduce date calculations

    /**
     * Represents a number of days from the beginning of year 0
     */
    private(set) int $epochDay {
        get => $this->epochDay ??= IsoCalendar::epochDayFromDate($this->year, $this->month, $this->day);
    }

    /** Indicates whether the current year is a leap year. */
    private(set) bool $isLeapYear { get => $this->isLeapYear ??= IsoCalendar::isLeapYear($this->year); }

    /** Represents the day of the week. */
    public int $dayOfWeek { get => $this->calculateDayOfWeek(); }

    /** The day of the year, typically ranging from 1 to 365, or 1 to 366 in a leap year. */
    public int $dayOfYear { get => IsoCalendar::dayOfYear($this->year, $this->month, $this->day); }

    /**
     * Calculates the ISO week number of the year for the current date.
     * The first week of the year is defined as the one that contains January 4th,
     * and weeks start on a Monday.
     */
    public int $weekOfYear { get => $this->calculateWeekOfYear(); }

    /**
     * Calculates the week of the month for the current date.
     * The value is determined by counting ISO weeks starting on Monday within the month.
     */
    public int $weekOfMonth { get => $this->calculateWeekOfMonth(); }

    /**
     * @var IsoEra Lazily derived ISO era based on the year sign.
     */
    public IsoEra $era { get => $this->era ??= IsoEra::fromYear($this->year); }

    /**
     * Creates a new instance from year-month-day in the ISO-8601 calendar system.
     *
     * Example usage:
     * <code>
     * $date = LocalDate::of(2024, 2, 29);
     * // Creates February 29th, 2024.
     * </code>
     *
     * Handling invalid dates:
     * <code>
     * try {
     *     $date = LocalDate::of(2024, 2, 29);
     * } catch (InvalidDate $e) {
     *     echo $e->getMessage();
     * }
     * </code>
     *
     * @param int        $year  Year component.
     * @param int<1, 12> $month Month of the year (1–12).
     * @param int<1, 31> $day   Day of the month.
     *
     * @throws InvalidDate When the date is not valid in the ISO calendar system.
     */
    public static function of(
        int $year,
        int $month,
        int $day,
    ): self {
        /** @phpstan-ignore-next-line staticMethod.alreadyNarrowedType reason: runtime validation must guard user-provided values */
        if (! IsoCalendar::isValidDate($year, $month, $day)) {
            throw new InvalidDate(\sprintf('Invalid date provided: %s-%s-%s.', $year, $month, $day));
        }

        return new self($year, $month, $day);
    }

    /**
     * Creates an instance of the class from the specified epoch day.
     * The epoch day is the number of days since 1970-01-01 (ISO calendar system).
     *
     * @param int $epochDay The number of days since the epoch of 1970-01-01.
     */
    public static function fromEpochDay(int $epochDay): self
    {
        [$year, $month, $day] = IsoCalendar::dateFromEpochDay($epochDay);

        $date = new self($year, $month, $day);
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
     * @throws InsufficientDateComponents If parse does not provide all necessary information about the input date.
     * @throws InvalidDate If there is no valid conversion possible.
     * @throws InvalidInput If any error occurs during parsing.
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
        \assert($year !== null);
        \assert($month !== null);
        \assert($day !== null);

        if (! self::isValidMonth($month)) {
            throw new InvalidDate(\sprintf('Month must be between 1 and 12, got %d.', $month));
        }

        if (! self::isValidDay($day)) {
            throw new InvalidDate(\sprintf('Day must be between 1 and 31, got %d.', $day));
        }

        return self::of(year: $year, month: $month, day: $day);
    }

    /**
     * Returns ISO 8601 Extended string "YYYY-MM-DD".
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
        return $this->plusDuration(new Duration($years, $months, $days));
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
        return $this->minusDuration(new Duration($years, $months, $days));
    }

    /**
     * Adds a duration to the current date.
     *
     * @param Duration $duration Duration composed of years, months, and days.
     *
     * @return self Adjusted date instance.
     */
    public function plusDuration(Duration $duration): self
    {
        $daysDelta = $duration->days
            + IsoCalendar::yearsMonthsToDays(
                year: $this->year,
                month: $this->month,
                day: $this->day,
                years: $duration->years,
                months: $duration->months,
            )
            + $duration->toTotalDays();

        return self::fromEpochDay($this->epochDay + $daysDelta);
    }

    /**
     * Subtracts a duration from the current date.
     *
     * @param Duration $duration Duration composed of years, months, and days.
     *
     * @return self Adjusted date instance.
     */
    public function minusDuration(Duration $duration): self
    {
        if (
            $duration->years === 0
            && $duration->months === 0
            && $duration->days === 0
            && $duration->hours === 0
            && $duration->minutes === 0
            && $duration->seconds === 0
            && $duration->nanos === 0
        ) {
            return $this;
        }

        return $this->plusDuration(new Duration(
            years: -$duration->years,
            months: -$duration->months,
            days: -$duration->days,
            hours: -$duration->hours,
            minutes: -$duration->minutes,
            seconds: -$duration->seconds,
            nanos: -$duration->nanos,
        ));
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
     * @return array{value: non-empty-string}
     */
    public function __serialize(): array
    {
        $date = (string) $this;
        \assert($date !== '');

        return ['value' => $date];
    }

    /**
     * @param array<string,mixed> $data
     */
    public function __unserialize(array $data): void
    {
        if (! \array_key_exists('value', $data)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s payload missing required keys.', self::class));
        }

        if (! \is_string($data['value']) || $data['value'] === '') {
            throw new \UnexpectedValueException(\sprintf('Serialized %s date must be a non-empty string.', self::class));
        }

        $formatter = DateTimeFormatter::of(DateTimeFormat::ExtendedIsoLocalDate);

        try {
            $fields = $formatter->parse($data['value']);
        } catch (InvalidInput) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s date is malformed.', self::class));
        }

        if (! $fields->supports(TemporalField::Year, TemporalField::Month, TemporalField::Day)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s date is malformed.', self::class));
        }

        $year = $fields->get(TemporalField::Year);
        $month = $fields->get(TemporalField::Month);
        $day = $fields->get(TemporalField::Day);
        \assert($year !== null);
        \assert($month !== null);
        \assert($day !== null);

        if (! self::isValidMonth($month)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s month must be between 1 and 12.', self::class));
        }

        if (! self::isValidDay($day)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s day must be between 1 and 31.', self::class));
        }

        /** @phpstan-ignore-next-line staticMethod.alreadyNarrowedType reason: runtime validation must guard user-provided values */
        if (! IsoCalendar::isValidDate($year, $month, $day)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s contains a date invalid for the ISO calendar system.', self::class));
        }

        self::__construct(year: $year, month: $month, day: $day);
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
            TemporalField::Era => $this->era->ordinal(),
            TemporalField::Year => $this->year,
            TemporalField::Month => $this->month,
            TemporalField::Day => $this->day,
            TemporalField::DayOfYear => $this->dayOfYear,
            TemporalField::DayOfWeek => $this->dayOfWeek,
            default => null,
        };
    }

    /**
     * Verifies whether all provided temporal fields are available in this instance.
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

    /**
     * @phpstan-assert-if-true int<1, 12> $month
     */
    private static function isValidMonth(int $month): bool
    {
        return $month >= 1 && $month <= 12;
    }

    /**
     * @phpstan-assert-if-true int<1, 31> $day
     */
    private static function isValidDay(int $day): bool
    {
        return $day >= 1 && $day <= 31;
    }

    /**
     * @return positive-int
     */
    private function calculateWeekOfYear(): int
    {
        $dayOfWeek = $this->dayOfWeek + 1; // convert to 1 (Mon) - 7 (Sun)
        $week = \intdiv($this->dayOfYear - $dayOfWeek + 10, 7);

        if ($week < 1) {
            return $this->weeksInWeekBasedYear($this->year - 1);
        }

        $weeksInYear = $this->weeksInWeekBasedYear($this->year);
        if ($week > $weeksInYear) {
            return 1;
        }

        return $week;
    }

    /**
     * @return positive-int
     *
     * @throws InvalidDate When the date is not valid in the ISO calendar system.
     */
    private function calculateWeekOfMonth(): int
    {
        $currentWeekStart = $this->epochDay - $this->dayOfWeek;
        $firstOfMonth = self::of($this->year, $this->month, 1);
        $firstWeekStart = $firstOfMonth->epochDay - $firstOfMonth->dayOfWeek;

        $week = \intdiv($currentWeekStart - $firstWeekStart, 7) + 1;

        if ($week <= 0) {
            throw new \LogicException('Calculated week of month must be positive.');
        }

        return $week;
    }

    /**
     * @return positive-int
     */
    private function weeksInWeekBasedYear(int $year): int
    {
        $start = $this->weekYearStartEpochDay($year);
        $next = $this->weekYearStartEpochDay($year + 1);
        assert($next > $start);

        $epochDelta = $next - $start;
        assert($epochDelta > 0);

        $weeks = \intdiv($epochDelta, 7);

        if ($weeks <= 0) {
            throw new \LogicException('Week-based year must contain at least one week.');
        }

        return $weeks;
    }

    private function weekYearStartEpochDay(int $year): int
    {
        $jan4Epoch = IsoCalendar::epochDayFromDate($year, 1, 4);
        $jan4 = self::fromEpochDay($jan4Epoch);

        return $jan4->epochDay - $jan4->dayOfWeek;
    }
}
