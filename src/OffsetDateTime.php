<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Temporal\Temporal;

/**
 * Date-time with a fixed UTC offset (no timezone/DST awareness).
 * 
 * Combines {@see LocalDateTime} with a fixed {@see ZoneOffset}.
 * Unlike {@see ZonedDateTime}, the offset never changes (no DST transitions).
 * 
 * Use cases:
 * - Storing timestamps with explicit offset
 * - APIs that work with ISO-8601 offset date-times
 * - When DST rules are not needed
 * 
 * Example:
 * ```php
 * $odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneOffset::of(2, 0));
 * echo $odt; // "2024-03-15T14:30:00+02:00"
 * ```
 */
final readonly class OffsetDateTime implements \Stringable
{
    private function __construct(
        public LocalDateTime $dateTime,
        public ZoneOffset $offset,
    ) {}

    /**
     * Create OffsetDateTime from components.
     *
     * @param int<1, 12> $month
     * @param int<1, 31> $day
     * @param int<0, 23> $hour
     * @param int<0, 59> $minute
     * @param int<0, 59> $second
     * @param int<0, 999999999> $nano
     */
    public static function of(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second = 0,
        int $nano = 0,
        ZoneOffset|null $offset = null,
    ): self {
        $dateTime = LocalDateTime::of($year, $month, $day, $hour, $minute, $second, $nano);
        $offset ??= ZoneOffset::UTC();

        return new self($dateTime, $offset);
    }

    /**
     * Create OffsetDateTime from LocalDateTime and ZoneOffset.
     */
    public static function ofDateTimeAndOffset(LocalDateTime $dateTime, ZoneOffset $offset): self
    {
        return new self($dateTime, $offset);
    }

    /**
     * Create OffsetDateTime from an Instant and ZoneOffset.
     */
    public static function ofInstant(Instant $instant, ZoneOffset $offset): self
    {
        $localEpochSecond = $instant->epochSecond + $offset->totalSeconds;
        
        $year = (int) \gmdate('Y', $localEpochSecond);
        $month = (int) \gmdate('n', $localEpochSecond);
        $day = (int) \gmdate('j', $localEpochSecond);
        $hour = (int) \gmdate('G', $localEpochSecond);
        $minute = (int) \gmdate('i', $localEpochSecond);
        $second = (int) \gmdate('s', $localEpochSecond);
        $nano = $instant->nanoAdjustment;
        
        // Assert ranges for PHPStan
        \assert($month >= 1 && $month <= 12);
        \assert($day >= 1 && $day <= 31);
        \assert($hour >= 0 && $hour <= 23);
        \assert($minute >= 0 && $minute <= 59);
        \assert($second >= 0 && $second <= 59);
        \assert($nano >= 0 && $nano <= 999999999);
        
        $dateTime = LocalDateTime::of($year, $month, $day, $hour, $minute, $second, $nano);

        return new self($dateTime, $offset);
    }

    /**
     * Get current OffsetDateTime in given offset (defaults to system timezone offset).
     */
    public static function now(ZoneOffset|null $offset = null): self
    {
        $instant = Instant::now();
        
        if ($offset === null) {
            // Use system timezone's current offset
            $systemOffset = (int) \date('Z', $instant->epochSecond);
            $offset = ZoneOffset::ofTotalSeconds($systemOffset);
        }

        return self::ofInstant($instant, $offset);
    }

    /**
     * Convert to Instant (UTC point in time).
     */
    public function toInstant(): Instant
    {
        // Get local timestamp and subtract offset to get UTC
        $localTs = \mktime(
            $this->dateTime->hour,
            $this->dateTime->minute,
            $this->dateTime->second,
            $this->dateTime->month,
            $this->dateTime->day,
            $this->dateTime->year,
        );

        if ($localTs === false) {
            throw new \RuntimeException('Invalid date/time');
        }

        $utcEpochSecond = $localTs - $this->offset->totalSeconds;

        return Instant::of($utcEpochSecond);
    }

    /**
     * Get epoch second (for backward compatibility and comparisons).
     * 
     * @deprecated Use toInstant()->epochSecond instead
     */
    public function toEpochSecond(): int
    {
        return $this->toInstant()->epochSecond;
    }

    /**
     * Change offset while keeping the same instant (UTC timestamp).
     * 
     * The local date-time will adjust accordingly.
     * 
     * Example:
     * ```php
     * $odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
     * // 2024-03-15T14:00:00+02:00
     * 
     * $odt2 = $odt1->withOffsetSameInstant(ZoneOffset::of(5, 0));
     * // 2024-03-15T17:00:00+05:00 (same instant, different local time)
     * ```
     */
    public function withOffsetSameInstant(ZoneOffset $newOffset): self
    {
        if ($this->offset->equalTo($newOffset)) {
            return $this;
        }

        $instant = $this->toInstant();

        return self::ofInstant($instant, $newOffset);
    }

    /**
     * Change offset while keeping the same local date-time.
     * 
     * The instant (UTC timestamp) will change accordingly.
     * 
     * Example:
     * ```php
     * $odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
     * // 2024-03-15T14:00:00+02:00
     * 
     * $odt2 = $odt1->withOffsetSameLocal(ZoneOffset::of(5, 0));
     * // 2024-03-15T14:00:00+05:00 (same local time, different instant)
     * ```
     */
    public function withOffsetSameLocal(ZoneOffset $newOffset): self
    {
        if ($this->offset->equalTo($newOffset)) {
            return $this;
        }

        return new self($this->dateTime, $newOffset);
    }

    /**
     * ISO-8601 format: "2024-03-15T14:30:00+02:00"
     */
    public function __toString(): string
    {
        return $this->dateTime . (string) $this->offset;
    }

    /**
     * Check equality with another OffsetDateTime.
     * 
     * Two OffsetDateTimes are equal if they represent the same instant
     * (same UTC timestamp), regardless of offset.
     */
    public function equals(self $other): bool
    {
        return $this->toInstant()->epochSecond === $other->toInstant()->epochSecond;
    }

    /**
     * Check if this is before another OffsetDateTime.
     */
    public function isBefore(self $other): bool
    {
        return $this->toInstant()->epochSecond < $other->toInstant()->epochSecond;
    }

    /**
     * Check if this is after another OffsetDateTime.
     */
    public function isAfter(self $other): bool
    {
        return $this->toInstant()->epochSecond > $other->toInstant()->epochSecond;
    }
}
