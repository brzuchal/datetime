<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\CalendarSystems\CalendarSystem;
use Brzuchal\DateTime\CalendarSystems\CalendarSystemRegistry;
use Brzuchal\DateTime\CalendarSystems\IsoCalendarSystem;
use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;
use Brzuchal\DateTime\Temporal\TimeUnit;

/**
 * Immutable combination of a {@see LocalDate} and {@see LocalTime} without timezone information.
 *
 * Example usage:
 * <code>
 * $dt = LocalDateTime::of(2025, 3, 17, 14, 30); // 2025-03-17T14:30:00
 * $date = LocalDate::of(2025, 3, 17);
 * $time = LocalTime::of(9, 15);
 * $dt2 = LocalDateTime::ofDateAndTime($date, $time); // 2025-03-17T09:15:00
 * echo (string) $dt2; // "2025-03-17T09:15:00"
 * </code>
 */
final class LocalDateTime implements TemporalAccessor
{

    public CalendarSystem $calendar {
        get => $this->date->calendar;
    }

    public int $year {
        get => $this->date->year;
    }

    /**
     * @return int<1, 12>
     */
    public int $month {
        get => $this->date->month;
    }

    /**
     * @return int<1, 31>
     */
    public int $day {
        get => $this->date->day;
    }

    /**
     * @return int<0, 23>
     */
    public int $hour {
        get => $this->time->hour;
    }

    /**
     * @return int<0, 59>
     */
    public int $minute {
        get => $this->time->minute;
    }

    /**
     * @return int<0, 59>
     */
    public int $second {
        get => $this->time->second;
    }

    /**
     * @return int<0, 999999999>
     */
    public int $nano {
        get => $this->time->nano;
    }

    private function __construct(public readonly LocalDate $date, public readonly LocalTime $time)
    {
    }

    /**
     * @return array{date: non-empty-string, calendar: non-empty-string, time: non-empty-string}
     */
    public function __serialize(): array
    {
        $datePayload = $this->date->__serialize();
        \assert($datePayload['date'] !== '');
        \assert($datePayload['calendar'] !== '');

        $time = (string) $this->time;
        \assert($time !== '');

        return [
            'date' => $datePayload['date'],
            'calendar' => $datePayload['calendar'],
            'time' => $time,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public function __unserialize(array $data): void
    {
        if (! isset($data['date'], $data['calendar'], $data['time'])) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s payload missing required keys.', self::class));
        }

        if (! \is_string($data['date']) || $data['date'] === '') {
            throw new \UnexpectedValueException(\sprintf('Serialized %s date must be a non-empty string.', self::class));
        }

        if (! \is_string($data['calendar']) || $data['calendar'] === '') {
            throw new \UnexpectedValueException(\sprintf('Serialized %s calendar must be a non-empty string.', self::class));
        }

        if (! \is_string($data['time']) || $data['time'] === '') {
            throw new \UnexpectedValueException(\sprintf('Serialized %s time must be a non-empty string.', self::class));
        }

        $calendar = CalendarSystemRegistry::get($data['calendar']);

        $length = 0;
        $parsed = \sscanf($data['date'], LocalDate::SERIALIZED_DATE_FORMAT . '%n', $yearValue, $monthValue, $dayValue, $length);
        if ($parsed < 3 || $length !== \strlen($data['date'])) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s date is malformed.', self::class));
        }

        if ($monthValue <= 0) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s month must be a positive integer.', self::class));
        }

        if ($dayValue <= 0) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s day must be a positive integer.', self::class));
        }

        if ($data['date'] !== \sprintf(LocalDate::SERIALIZED_DATE_FORMAT, $yearValue, $monthValue, $dayValue)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s date contains invalid numeric components.', self::class));
        }

        if (! $calendar->isValidDate($yearValue, $monthValue, $dayValue)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s contains a date invalid for the provided calendar system.', self::class));
        }

        $timeComponents = self::parseSerializedTime($data['time']);

        $date = LocalDate::of($yearValue, $monthValue, $dayValue, $calendar);
        $time = LocalTime::of(
            $timeComponents['hour'],
            $timeComponents['minute'],
            $timeComponents['second'],
            $timeComponents['nano'],
        );

        self::__construct($date, $time);
    }

    /**
     * Creates a LocalDateTime from year, month, day and time components.
     *
     * Basic example:
     * <code>
     * $dt = LocalDateTime::of(2025, 3, 17, 14, 30); // 2025-03-17T14:30:00
     * </code>
     *
     * With a custom calendar system:
     * <code>
     * $dt = LocalDateTime::of(2025, 3, 17, 0, 0, 0, 0, new IsoCalendarSystem());
     * </code>
     *
     * @param int<1, 12> $month
     * @param int<1, 31> $day
     * @param int<0, 23> $hour
     * @param int<0, 59> $minute
     * @param int<0, 59> $second
     * @param int<0, 999999999> $nano
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public static function of(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        int $nano = 0,
        CalendarSystem $calendarSystem = new IsoCalendarSystem(),
    ): self {
        $date = LocalDate::of($year, $month, $day, $calendarSystem);
        $time = LocalTime::of($hour, $minute, $second, $nano);

        return new self($date, $time);
    }

    /**
     * Creates a LocalDateTime by combining a LocalDate and a LocalTime.
     *
     * @param LocalDate $date The date component.
     * @param LocalTime $time The time component.
     *
     * @return self The combined LocalDateTime instance.
     */
    public static function ofDateAndTime(LocalDate $date, LocalTime $time): self
    {
        return new self($date, $time);
    }

    /**
     * Creates a LocalDateTime from an Instant in the given calendar system.
     *
     * @param Instant $instant        The instant to convert.
     * @param CalendarSystem $calendarSystem Target calendar system (defaults to ISO).
     *
     * @return self LocalDateTime representing the instant in the specified calendar system.
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public static function ofInstant(Instant $instant, CalendarSystem $calendarSystem = new IsoCalendarSystem()): self
    {
        $epochDay = $instant->epochDay;
        $secondOfDay = self::floorMod($instant->epochSecond, TimeUnit::SECONDS_PER_DAY);
        $nanoOfDay = $secondOfDay * TimeUnit::NANOS_PER_SECOND + $instant->nanoAdjustment;
        \assert($nanoOfDay >= 0 && $nanoOfDay < TimeUnit::NANOS_PER_DAY);

        $date = LocalDate::fromEpochDay($epochDay, $calendarSystem);
        $time = LocalTime::ofNanoOfDay($nanoOfDay);

        return new self($date, $time);
    }

    /**
     * Returns a copy of this LocalDateTime with the date component replaced.
     *
     * @param LocalDate $date The new date component.
     *
     * @return self A new LocalDateTime instance, or this instance if unchanged.
     */
    public function withDate(LocalDate $date): self
    {
        if ($date === $this->date) {
            return $this;
        }

        return new self($date, $this->time);
    }

    /**
     * Returns a copy of this LocalDateTime with the time component replaced.
     *
     * @param LocalTime $time The new time component.
     *
     * @return self A new LocalDateTime instance, or this instance if unchanged.
     */
    public function withTime(LocalTime $time): self
    {
        if ($time === $this->time) {
            return $this;
        }

        return new self($this->date, $time);
    }

    /**
     * Returns a copy of this LocalDateTime with selected time components replaced.
     *
     * Any parameter set to null will keep the current value. Values are validated against allowed ranges.
     *
     * @param int<0, 23>|null $hour   Hour of the day.
     * @param int<0, 59>|null $minute Minute of hour.
     * @param int<0, 59>|null $second Second of minute.
     * @param int<0, 999999999>|null $nano   Nanosecond of second.
     *
     * @return self A new LocalDateTime with updated time, or this instance if unchanged.
     *
     * @throws InvalidTime If the value is out of range.
     */
    public function withTimeComponents(int|null $hour = null, int|null $minute = null, int|null $second = null, int|null $nano = null): self
    {
        $time = LocalTime::of(
            $hour ?? $this->hour,
            $minute ?? $this->minute,
            $second ?? $this->second,
            $nano ?? $this->nano,
        );

        if ($time->equals($this->time)) {
            return $this;
        }

        return new self($this->date, $time);
    }

    /**
     * Returns a copy of this LocalDateTime with the specified duration added.
     *
     * Date-based units (years, months, days) are applied to the date first, then time-based units are applied.
     * Time overflow or underflow adjusts the date accordingly.
     *
     * @param Duration $duration The duration to add; may contain years, months, days, hours, minutes, seconds, nanos.
     *
     * @return self A new LocalDateTime with the adjustment applied, or this instance if the duration is zero.
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public function plus(Duration $duration): self
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

        $date = $this->date->plus(
            years: $duration->years,
            months: $duration->months,
            days: $duration->days,
        );

        $currentNanoOfDay = $this->time->toNanoOfDay();
        $deltaNano = $this->durationNanoAdjustment($duration);
        $newNanoTotal = $currentNanoOfDay + $deltaNano;

        $dayOverflow = self::dayOverflowFromNanos($newNanoTotal);
        $nanoOfDay = self::floorMod($newNanoTotal, TimeUnit::NANOS_PER_DAY);
        \assert($nanoOfDay >= 0 && $nanoOfDay < TimeUnit::NANOS_PER_DAY);

        if ($dayOverflow !== 0) {
            $date = $date->plus(days: $dayOverflow);
        }

        $time = LocalTime::ofNanoOfDay($nanoOfDay);

        return new self($date, $time);
    }

    /**
     * Returns a copy of this LocalDateTime with the specified duration subtracted.
     *
     * This is equivalent to calling plus() with the negated duration.
     *
     * @param Duration $duration The duration to subtract.
     *
     * @return self A new LocalDateTime with the adjustment applied, or this instance if the duration is zero.
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public function minus(Duration $duration): self
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

        return $this->plus(new Duration(
            years: -$duration->years,
            months: -$duration->months,
            days: -$duration->days,
            hours: -$duration->hours,
            minutes: -$duration->minutes,
            seconds: -$duration->seconds,
            nanos: -$duration->nanos,
        ));
    }

    /**
     * Returns a copy of this LocalDateTime with the specified number of years added to the date component.
     *
     * @param int $years Years to add (can be negative).
     * @return self A new LocalDateTime with the adjusted date, or this instance if zero.
     *
     * @throws InvalidDate If the date component is out of range.
     */
    public function plusYears(int $years): self
    {
        if ($years === 0) {
            return $this;
        }

        return $this->withDate($this->date->plus(years: $years));
    }

    /**
     * Returns a copy of this LocalDateTime with the specified number of months added to the date component.
     *
     * @param int $months Months to add (can be negative).
     * @return self A new LocalDateTime with the adjusted date, or this instance if zero.
     *
     * @throws InvalidDate If the date component is out of range.
     */
    public function plusMonths(int $months): self
    {
        if ($months === 0) {
            return $this;
        }

        return $this->withDate($this->date->plus(months: $months));
    }

    /**
     * Returns a copy of this LocalDateTime with the specified number of days added to the date component.
     *
     * @param int $days Days to add (can be negative).
     *
     * @return self A new LocalDateTime with the adjusted date, or this instance if zero.
     *
     * @throws InvalidDate If the date component is out of range.
     */
    public function plusDays(int $days): self
    {
        if ($days === 0) {
            return $this;
        }

        return $this->withDate($this->date->plus(days: $days));
    }

    /**
     * Returns a copy of this LocalDateTime with the specified number of hours added.
     *
     * @param int $hours Hours to add (can be negative).
     *
     * @return self A new LocalDateTime with the adjustment applied, or this instance if zero.
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public function plusHours(int $hours): self
    {
        if ($hours === 0) {
            return $this;
        }

        return $this->plus(new Duration(hours: $hours));
    }

    /**
     * Returns a copy of this LocalDateTime with the specified number of minutes added.
     *
     * @param int $minutes Minutes to add (can be negative).
     *
     * @return self A new LocalDateTime with the adjustment applied, or this instance if zero.
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public function plusMinutes(int $minutes): self
    {
        if ($minutes === 0) {
            return $this;
        }

        return $this->plus(new Duration(minutes: $minutes));
    }

    /**
     * Returns a copy of this LocalDateTime with the specified number of seconds added.
     *
     * @param int $seconds Seconds to add (can be negative).
     *
     * @return self A new LocalDateTime with the adjustment applied, or this instance if zero.
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public function plusSeconds(int $seconds): self
    {
        if ($seconds === 0) {
            return $this;
        }

        return $this->plus(new Duration(seconds: $seconds));
    }

    /**
     * Returns a copy of this LocalDateTime with the specified number of nanoseconds added.
     *
     * @param int $nanos Nanoseconds to add (can be negative).
     *
     * @return self A new LocalDateTime with the adjustment applied, or this instance if zero.
     *
     * @throws InvalidDate If the date component is out of range.
     * @throws InvalidTime If the time component is out of range.
     */
    public function plusNanos(int $nanos): self
    {
        if ($nanos === 0) {
            return $this;
        }

        return $this->plus(new Duration(nanos: $nanos));
    }

    /**
     * Returns the date component of this LocalDateTime.
     *
     * @return LocalDate The LocalDate part.
     */
    public function toLocalDate(): LocalDate
    {
        return $this->date;
    }

    /**
     * Returns the time component of this LocalDateTime.
     *
     * @return LocalTime The LocalTime part.
     */
    public function toLocalTime(): LocalTime
    {
        return $this->time;
    }

    /**
     * Compares this LocalDateTime to another.
     *
     * First compares the dates by epoch day, then the times by nano-of-day.
     *
     * @param self $other The other LocalDateTime to compare to.
     *
     * @return int -1, 0 or 1 if this instance is earlier than, equal to, or later than the other.
     */
    public function compareTo(self $other): int
    {
        $dateComparison = $this->date->epochDay <=> $other->date->epochDay;
        if ($dateComparison !== 0) {
            return $dateComparison;
        }

        return $this->time->toNanoOfDay() <=> $other->time->toNanoOfDay();
    }

    /**
     * Checks if this LocalDateTime is equal to another.
     *
     * Equality is based on year, month, day and the time components.
     *
     * @param self $other The other LocalDateTime.
     *
     * @return bool True if both date and time components are equal.
     */
    public function equals(self $other): bool
    {
        return $this->date->year === $other->date->year
            && $this->date->month === $other->date->month
            && $this->date->day === $other->date->day
            && $this->time->equals($other->time);
    }

    /**
     * Formats this LocalDateTime using ISO-8601 extended format: YYYY-MM-DDTHH:MM:SS[.nnnnnnnnn].
     *
     * @return string The ISO-8601 string representation of this date-time.
     */
    public function __toString(): string
    {
        return (string) $this->date . 'T' . (string) $this->time;
    }

    /**
     * Gets the value of the specified temporal field if supported by LocalDateTime.
     *
     * Time-related fields are delegated to the time component; date-related fields to the date component.
     *
     * @param TemporalField $field The field to query.
     *
     * @return int|null The field value, or null if the field is not supported.
     */
    public function get(TemporalField $field): int|null
    {
        if (
            \in_array(
                $field,
                [
                    TemporalField::Hour,
                    TemporalField::Hour12,
                    TemporalField::Minute,
                    TemporalField::Second,
                    TemporalField::AmPm,
                ],
                true,
            )
        ) {
            return $this->time->get($field);
        }

        switch ($field) {
            case TemporalField::WeekOfYear:
                return $this->date->weekOfYear;

            case TemporalField::WeekOfMonth:
                return $this->date->weekOfMonth;

            case TemporalField::Era:
                return $this->date->era->ordinal;

            case TemporalField::Year:
                return $this->year;

            case TemporalField::Month:
                return $this->month;

            case TemporalField::Day:
                return $this->day;

            case TemporalField::DayOfYear:
                return $this->date->dayOfYear;

            case TemporalField::DayOfWeek:
                return $this->date->dayOfWeek;

            default:
                return null;
        }
    }

    /**
     * Checks if all provided temporal fields are supported by LocalDateTime.
     *
     * Supported fields include both date and time-related fields.
     *
     * @param TemporalField ...$fields Fields to check.
     *
     * @return bool True if all fields are supported; false otherwise.
     */
    public function supports(TemporalField ...$fields): bool
    {
        foreach ($fields as $field) {
            if (
                !\in_array($field, [
                    TemporalField::Era,
                    TemporalField::Year,
                    TemporalField::Month,
                    TemporalField::Day,
                    TemporalField::DayOfYear,
                    TemporalField::DayOfWeek,
                    TemporalField::WeekOfYear,
                    TemporalField::WeekOfMonth,
                    TemporalField::Hour,
                    TemporalField::Hour12,
                    TemporalField::Minute,
                    TemporalField::Second,
                    TemporalField::AmPm,
                ], true)
            ) {
                return false;
            }
        }

        return true;
    }

    private function durationNanoAdjustment(Duration $duration): int
    {
        return $duration->hours * TimeUnit::NANOS_PER_HOUR
            + $duration->minutes * TimeUnit::NANOS_PER_MINUTE
            + $duration->seconds * TimeUnit::NANOS_PER_SECOND
            + $duration->nanos;
    }

    /**
     * @return array{hour:int<0,23>,minute:int<0,59>,second:int<0,59>,nano:int<0,999999999>}
     */
    private static function parseSerializedTime(string $time): array
    {
        $parts = \explode('.', $time, 2);
        $base = $parts[0];
        $fraction = $parts[1] ?? '';

        $scan = \sscanf($base, LocalTime::SERIALIZED_TIME_FORMAT);
        if ($scan === null || \count($scan) !== 3) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s time is malformed.', self::class));
        }

        [$hour, $minute, $second] = $scan;

        if ($base !== \sprintf(LocalTime::SERIALIZED_TIME_FORMAT, $hour, $minute, $second)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s time contains invalid numeric components.', self::class));
        }

        if ($fraction !== '') {
            if (! \ctype_digit($fraction) || \strlen($fraction) > 9) {
                throw new \UnexpectedValueException(\sprintf('Serialized %s fractional seconds are malformed.', self::class));
            }

            $nano = (int) ($fraction . \str_repeat('0', 9 - \strlen($fraction)));
        } else {
            $nano = 0;
        }

        if (
            $hour > 23
            || $minute > 59
            || $second > 59
            || $nano > TimeUnit::NANOS_PER_SECOND - 1
        ) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s time contains out-of-range values.', self::class));
        }

        return [
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
            'nano' => $nano,
        ];
    }

    /**
     * @param int $nanoseconds
     * @return int
     */
    private static function dayOverflowFromNanos(int $nanoseconds): int
    {
        $quotient = \intdiv($nanoseconds, TimeUnit::NANOS_PER_DAY);

        if ($nanoseconds < 0 && $nanoseconds % TimeUnit::NANOS_PER_DAY !== 0) {
            return $quotient - 1;
        }

        return $quotient;
    }

    private static function floorMod(int $value, int $mod): int
    {
        $result = $value % $mod;

        if ($result < 0) {
            $result += $mod;
        }

        return $result;
    }
}
