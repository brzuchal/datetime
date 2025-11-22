<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Format\DurationFormat;
use Brzuchal\DateTime\Format\DurationFormatter;
use Brzuchal\DateTime\Temporal\TimeUnit;

/**
 * Initialises a new instance of the class with the specified time components.
 */
final readonly class Duration
{
    /**
     * Initialises a new instance of the class with the specified time components.
     *
     * @param int $hours   The number of hours to initialise. Default is 0.
     * @param int $minutes The number of minutes to initialise. Default is 0.
     * @param int $seconds The number of seconds to initialise. Default is 0.
     * @param int $nanos   The number of nanoseconds to initialise. Default is 0.
     *
     * @return void
     */
    public function __construct(
        public int $hours = 0,
        public int $minutes = 0,
        public int $seconds = 0,
        public int $nanos = 0,
    ) {}

    /**
     * Parses an ISO 8601 period string and creates a new instance of the class.
     *
     * @param non-empty-string $text The ISO 8601 period string to parse.
     *
     * @return self A new instance of the class initialised with the parsed years, months, and days.
     *
     * @throws InvalidDuration If the provided string does not match the ISO 8601 period format.
     */
    public static function parse(string $text, DurationFormat $format = DurationFormat::IsoStandard): self
    {
        return DurationFormatter::of($format)->parse($text);
    }

    /**
     * Formats the duration using the specified formatting style.
     *
     * @param DurationFormat $format The formatting style to apply. Default is DurationFormat::IsoStandard.
     * @return string The formatted duration as a string.
     */
    public function format(DurationFormat $format = DurationFormat::IsoStandard): string
    {
        return DurationFormatter::of($format)->format($this);
    }

    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Calculates how many full days are represented by the time components of the duration.
     */
    public function toTotalDays(): int
    {
        $totalSeconds = ($this->hours * TimeUnit::SECONDS_PER_HOUR)
            + ($this->minutes * TimeUnit::SECONDS_PER_MINUTE)
            + $this->seconds;

        $totalNanos = ($totalSeconds * TimeUnit::NANOS_PER_SECOND) + $this->nanos;

        if ($totalNanos === 0) {
            return 0;
        }

        return \intdiv($totalNanos, TimeUnit::NANOS_PER_DAY);
    }

    // factories

    /**
     * Creates a new instance by a given number of hours.
     *
     * @param int $hours The number of hours to create the instance with.
     *
     * @return self A new instance initialised with the specified hours.
     */
    public static function hours(int $hours): self
    {
        return new self(hours: $hours);
    }

    /**
     * Creates a new instance by a given number of minutes.
     *
     * @param int $minutes The number of minutes to create the instance with.
     *
     * @return self A new instance initialised with the specified minutes.
     */
    public static function minutes(int $minutes): self
    {
        return new self(minutes: $minutes);
    }

    /**
     * Creates an instance with the specified number of seconds.
     *
     * @param int $seconds The number of seconds to set.
     *
     * @return self An instance with the given seconds.
     */
    public static function seconds(int $seconds): self
    {
        return new self(seconds: $seconds);
    }

    /**
     * Creates an instance with the specified number of nanoseconds.
     *
     * @param int $nanos The number of nanoseconds to set.
     *
     * @return self An instance with the given nanoseconds.
     */
    public static function nanos(int $nanos): self
    {
        return new self(nanos: $nanos);
    }

    // fluent mutators

    /**
     * Returns a new instance with the specified period or duration added to the current values.
     *
     * @param int $hours   The number of hours to add.
     * @param int $minutes The number of minutes to add.
     * @param int $seconds The number of seconds to add.
     * @param int $nanos   The number of nanoseconds to add.
     *
     * @return self A new instance with the updated values.
     */
    public function plus(int $hours = 0, int $minutes = 0, int $seconds = 0, int $nanos = 0): self
    {
        return new self(
            hours: $this->hours + $hours,
            minutes: $this->minutes + $minutes,
            seconds: $this->seconds + $seconds,
            nanos: $this->nanos + $nanos,
        );
    }

    /**
     * Creates a new instance with the specified time values subtracted.
     *
     * @param int $hours   The number of hours to subtract.
     * @param int $minutes The number of minutes to subtract.
     * @param int $seconds The number of seconds to subtract.
     * @param int $nanos   The number of nanoseconds to subtract.
     *
     * @return self A new instance with the adjusted time values.
     */
    public function minus(int $hours = 0, int $minutes = 0, int $seconds = 0, int $nanos = 0): self
    {
        return new self(
            hours: $this->hours - $hours,
            minutes: $this->minutes - $minutes,
            seconds: $this->seconds - $seconds,
            nanos: $this->nanos - $nanos,
        );
    }
    /**
     * Returns a new instance with the specified duration added to the current values.
     *
     * @param Duration $duration The duration to add.
     *
     * @return self A new instance with the updated values.
     */
    public function plusDuration(Duration $duration): self
    {
        return new self(
            hours: $this->hours + $duration->hours,
            minutes: $this->minutes + $duration->minutes,
            seconds: $this->seconds + $duration->seconds,
            nanos: $this->nanos + $duration->nanos,
        );
    }

    /**
     * Creates a new instance with the specified duration subtracted.
     *
     * @param Duration $duration The duration to subtract.
     *
     * @return self A new instance with the adjusted time values.
     */
    public function minusDuration(Duration $duration): self
    {
        return new self(
            hours: $this->hours - $duration->hours,
            minutes: $this->minutes - $duration->minutes,
            seconds: $this->seconds - $duration->seconds,
            nanos: $this->nanos - $duration->nanos,
        );
    }

    /**
     * Returns a new instance with the values multiplied by the specified scalar.
     *
     * @param int $scalar The scalar to multiply by.
     *
     * @return self A new instance with the multiplied values.
     */
    public function multipliedBy(int $scalar): self
    {
        if ($scalar === 1) {
            return $this;
        }

        return new self(
            hours: $this->hours * $scalar,
            minutes: $this->minutes * $scalar,
            seconds: $this->seconds * $scalar,
            nanos: $this->nanos * $scalar,
        );
    }

    /**
     * Returns a new instance with the values negated.
     *
     * @return self A new instance with the negated values.
     */
    public function negated(): self
    {
        return $this->multipliedBy(-1);
    }
}
