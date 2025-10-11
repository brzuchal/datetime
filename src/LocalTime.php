<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;
use Brzuchal\DateTime\Temporal\TimeUnit;

/**
 * Immutable representation of a time-of-day without a date or timezone.
 *
 * Example usage:
 * <code>
 * $time = LocalTime::of(14, 30); // 14:30:00
 * $midnight = LocalTime::midnight(); // 00:00:00
 * $noon = LocalTime::noon(); // 12:00:00
 * echo (string) $time; // "14:30:00"
 * </code>
 */
final readonly class LocalTime implements TemporalAccessor
{
    private const array SUPPORTED_FIELDS = [
        TemporalField::Hour,
        TemporalField::Hour12,
        TemporalField::Minute,
        TemporalField::Second,
        TemporalField::AmPm,
    ];

    public const string SERIALIZED_TIME_FORMAT = '%02d:%02d:%02d';

    /**
     * @param int<0,23> $hour   Hour of day in a 24-hour clock.
     * @param int<0,59> $minute Minute of hour.
     * @param int<0,59> $second Second of minute.
     * @param int<0,999999999> $nano   Nanosecond of second.
     */
    private function __construct(
        public int $hour,
        public int $minute,
        public int $second,
        public int $nano,
    ) {}

    /**
     * Create a LocalTime from hour, minute, second and nanosecond components.
     *
     * Basic example:
     * <code>
     * $time = LocalTime::of(9, 15); // 09:15:00
     * $time = LocalTime::of(23, 59, 59, 1); // 23:59:59.000000001
     * </code>
     *
     * @param int<0,23> $hour   Hour of day in a 24-hour clock.
     * @param int<0,59> $minute Minute of hour.
     * @param int<0,59> $second Second of minute.
     * @param int<0,999999999> $nano   Nanosecond of second.
     *
     * @return self The created LocalTime instance.
     *
     * @throws InvalidTime If any component is out of the allowed range.
     */
    public static function of(int $hour, int $minute = 0, int $second = 0, int $nano = 0): self
    {
        self::ensureWithinLimits($hour, 23, 'hour');
        self::ensureWithinLimits($minute, 59, 'minute');
        self::ensureWithinLimits($second, 59, 'second');
        self::ensureWithinLimits($nano, TimeUnit::NANOS_PER_SECOND - 1, 'nanosecond');

        return new self($hour, $minute, $second, $nano);
    }

    /**
     * Create a LocalTime from a second-of-day value (0..86399).
     *
     * @param int<0,86399> $secondOfDay Number of seconds since midnight.
     *
     * @return self Created LocalTime.
     *
     * @throws InvalidTime If the value is out of range.
     */
    public static function ofSecondOfDay(int $secondOfDay): self
    {
        self::ensureWithinLimits($secondOfDay, TimeUnit::SECONDS_PER_DAY - 1, 'second of day');

        $hour = \intdiv($secondOfDay, TimeUnit::SECONDS_PER_HOUR);
        \assert($hour >= 0 && $hour <= 23);
        $minute = \intdiv($secondOfDay % TimeUnit::SECONDS_PER_HOUR, TimeUnit::SECONDS_PER_MINUTE);
        \assert($minute >= 0 && $minute <= 59);
        $second = $secondOfDay % TimeUnit::SECONDS_PER_MINUTE;

        return new self($hour, $minute, $second, 0);
    }

    /**
     * Create a LocalTime from a nanosecond-of-day value (0..86_399_999_999_999).
     *
     * @param int<0,86399999999999> $nanoOfDay Number of nanoseconds since midnight.
     *
     * @return self Created LocalTime.
     *
     * @throws InvalidTime If the value is out of range.
     */
    public static function ofNanoOfDay(int $nanoOfDay): self
    {
        self::ensureWithinLimits($nanoOfDay, TimeUnit::NANOS_PER_DAY - 1, 'nanosecond of day');

        $hour = \intdiv($nanoOfDay, TimeUnit::NANOS_PER_HOUR);
        \assert($hour >= 0 && $hour <= 23);
        $nanoOfDay -= $hour * TimeUnit::NANOS_PER_HOUR;

        $minute = \intdiv($nanoOfDay, TimeUnit::NANOS_PER_MINUTE);
        \assert($minute >= 0 && $minute <= 59);
        $nanoOfDay -= $minute * TimeUnit::NANOS_PER_MINUTE;

        $second = \intdiv($nanoOfDay, TimeUnit::NANOS_PER_SECOND);
        \assert($second >= 0 && $second <= 59);
        $nano = $nanoOfDay - $second * TimeUnit::NANOS_PER_SECOND;
        \assert($nano >= 0 && $nano <= 999999999);

        return new self($hour, $minute, $second, $nano);
    }

    /**
     * Return the time at midnight (00:00:00.000000000).
     *
     * @return self Midnight constant value.
     */
    public static function midnight(): self
    {
        return new self(0, 0, 0, 0);
    }

    /**
     * Return the time at noon (12:00:00.000000000).
     *
     * @return self Noon constant value.
     */
    public static function noon(): self
    {
        return new self(12, 0, 0, 0);
    }

    /**
     * Returns a copy of this time with the specified amount added.
     *
     * The addition wraps within the 24-hour day.
     *
     * @param int $hours   Hours to add (can be negative).
     * @param int $minutes Minutes to add (can be negative).
     * @param int $seconds Seconds to add (can be negative).
     * @param int $nanos   Nanoseconds to add (can be negative).
     *
     * @return self A new LocalTime with the adjustment applied, or this instance if no change.
     *
     * @throws InvalidTime If the value is out of range.
     */
    public function plus(int $hours = 0, int $minutes = 0, int $seconds = 0, int $nanos = 0): self
    {
        if ($hours === 0 && $minutes === 0 && $seconds === 0 && $nanos === 0) {
            return $this;
        }

        $delta = ($hours % TimeUnit::HOURS_PER_DAY) * TimeUnit::NANOS_PER_HOUR
            + ($minutes % TimeUnit::MINUTES_PER_DAY) * TimeUnit::NANOS_PER_MINUTE
            + ($seconds % TimeUnit::SECONDS_PER_DAY) * TimeUnit::NANOS_PER_SECOND
            + $nanos;

        $newNanoOfDay = self::normalizeNanoOfDay($this->toNanoOfDay() + $delta);

        return self::ofNanoOfDay($newNanoOfDay);
    }

    /**
     * Returns a copy of this time with the specified amount subtracted.
     *
     * This is equivalent to calling plus() with the negated amounts.
     *
     * @param int $hours   Hours to subtract (can be negative).
     * @param int $minutes Minutes to subtract (can be negative).
     * @param int $seconds Seconds to subtract (can be negative).
     * @param int $nanos   Nanoseconds to subtract (can be negative).
     *
     * @return self A new LocalTime with the adjustment applied.
     *
     * @throws InvalidTime If the value is out of range.
     */
    public function minus(int $hours = 0, int $minutes = 0, int $seconds = 0, int $nanos = 0): self
    {
        return $this->plus(-$hours, -$minutes, -$seconds, -$nanos);
    }

    /**
     * Returns a copy of this time with the specified nano-of-day.
     *
     * @param int<0,86399999999999> $nanoOfDay Nanosecond of day to set.
     *
     * @return self A new LocalTime with the given nano-of-day, or this instance if unchanged.
     *
     * @throws InvalidTime If the value is out of range.
     */
    public function withNanoOfDay(int $nanoOfDay): self
    {
        if ($nanoOfDay === $this->toNanoOfDay()) {
            return $this;
        }

        return self::ofNanoOfDay($nanoOfDay);
    }

    /**
     * Returns the number of nanoseconds since midnight for this time.
     *
     * @return int Nanoseconds since midnight (0..86_399_999_999_999).
     */
    public function toNanoOfDay(): int
    {
        return $this->hour * TimeUnit::NANOS_PER_HOUR
            + $this->minute * TimeUnit::NANOS_PER_MINUTE
            + $this->second * TimeUnit::NANOS_PER_SECOND
            + $this->nano;
    }

    /**
     * Returns the number of seconds since midnight for this time.
     *
     * @return int Seconds since midnight (0..86_399).
     */
    public function toSecondOfDay(): int
    {
        return $this->hour * TimeUnit::SECONDS_PER_HOUR
            + $this->minute * TimeUnit::SECONDS_PER_MINUTE
            + $this->second;
    }

    /**
     * Gets the value of the specified temporal field if supported.
     *
     * @param TemporalField $field Field to query.
     *
     * @return int|null The field value, or null if the field is not supported.
     */
    public function get(TemporalField $field): int|null
    {
        return match ($field) {
            TemporalField::Hour => $this->hour,
            TemporalField::Hour12 => $this->hour === 0 ? 12 : (($this->hour - 1) % 12) + 1,
            TemporalField::Minute => $this->minute,
            TemporalField::Second => $this->second,
            TemporalField::AmPm => $this->hour >= 12 ? 1 : 0,
            default => null,
        };
    }

    /**
     * Checks if all provided temporal fields are supported by LocalTime.
     *
     * @param TemporalField ...$fields Fields to check.
     *
     * @return bool True if all fields are supported; false otherwise.
     */
    public function supports(TemporalField ...$fields): bool
    {
        foreach ($fields as $field) {
            if (!\in_array($field, self::SUPPORTED_FIELDS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compares this time to another time.
     *
     * @param self $other The other LocalTime to compare to.
     *
     * @return int -1, 0 or 1 if this time is less than, equal to, or greater than the other.
     */
    public function compareTo(self $other): int
    {
        return $this->toNanoOfDay() <=> $other->toNanoOfDay();
    }

    /**
     * Checks if this time is equal to another time.
     *
     * @param self $other The other LocalTime to compare.
     *
     * @return bool True if both times have identical hour, minute, second and nano values.
     */
    public function equals(self $other): bool
    {
        return $this->hour === $other->hour
            && $this->minute === $other->minute
            && $this->second === $other->second
            && $this->nano === $other->nano;
    }

    /**
     * Formats this time using ISO-8601 extended format (HH:MM:SS[.nnnnnnnnn]).
     *
     * @return string The ISO-8601 string representation of this time.
     */
    public function __toString(): string
    {
        $base = \sprintf(self::SERIALIZED_TIME_FORMAT, $this->hour, $this->minute, $this->second);

        if ($this->nano === 0) {
            return $base;
        }

        $fraction = \str_pad((string) $this->nano, 9, '0', STR_PAD_LEFT);
        $fraction = \rtrim($fraction, '0');

        return $base . '.' . $fraction;
    }

    /**
     * @return array{value: non-empty-string}
     */
    public function __serialize(): array
    {
        $time = (string) $this;
        \assert($time !== '');

        return ['value' => $time];
    }

    /**
     * @param array<string,mixed> $data
     * @throws InvalidTime
     */
    public function __unserialize(array $data): void
    {
        if (! isset($data['value'])) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s payload missing required key.', self::class));
        }

        if (! \is_string($data['value']) || $data['value'] === '') {
            throw new \UnexpectedValueException(\sprintf('Serialized %s time must be a non-empty string.', self::class));
        }

        $components = self::parseSerializedTime($data['value']);

        self::ensureWithinLimits($components['hour'], 23, 'hour');
        self::ensureWithinLimits($components['minute'], 59, 'minute');
        self::ensureWithinLimits($components['second'], 59, 'second');
        self::ensureWithinLimits($components['nano'], TimeUnit::NANOS_PER_SECOND - 1, 'nanosecond');

        self::__construct(
            hour: $components['hour'],
            minute: $components['minute'],
            second: $components['second'],
            nano: $components['nano'],
        );
    }

    /**
     * @return array{hour:int<0,23>,minute:int<0,59>,second:int<0,59>,nano:int<0,999999999>}
     */
    private static function parseSerializedTime(string $time): array
    {
        $parts = \explode('.', $time, 2);
        $base = $parts[0];
        $fraction = $parts[1] ?? '';

        $scan = \sscanf($base, self::SERIALIZED_TIME_FORMAT);
        if (! \is_array($scan) || ! isset($scan[0], $scan[1], $scan[2])) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s time is malformed.', self::class));
        }

        [$hour, $minute, $second] = $scan;

        if ($base !== \sprintf(self::SERIALIZED_TIME_FORMAT, $hour, $minute, $second)) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s time contains invalid numeric components.', self::class));
        }

        if ($hour < 0 || $hour > 23) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s hour must be between 0 and 23.', self::class));
        }

        if ($minute < 0 || $minute > 59) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s minute must be between 0 and 59.', self::class));
        }

        if ($second < 0 || $second > 59) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s second must be between 0 and 59.', self::class));
        }

        if ($fraction !== '') {
            if (! \ctype_digit($fraction) || \strlen($fraction) > 9) {
                throw new \UnexpectedValueException(\sprintf('Serialized %s fractional seconds are malformed.', self::class));
            }

            $nano = (int) ($fraction . \str_repeat('0', 9 - \strlen($fraction)));
        } else {
            $nano = 0;
        }

        if ($nano < 0 || $nano > TimeUnit::NANOS_PER_SECOND - 1) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s nanosecond must be between 0 and 999999999.', self::class));
        }

        return [
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
            'nano' => $nano,
        ];
    }

    /**
     * @throws InvalidTime
     */
    private static function ensureWithinLimits(int $value, int $max, string $name): void
    {
        if ($value < 0 || $value > $max) {
            throw new InvalidTime(\sprintf('Invalid %s value: %d.', $name, $value));
        }
    }

    /**
     * @return int<0,86399999999999>
     */
    private static function normalizeNanoOfDay(int $value): int
    {
        $result = $value % TimeUnit::NANOS_PER_DAY;

        if ($result < 0) {
            $result += TimeUnit::NANOS_PER_DAY;
        }

        return $result;
    }
}
