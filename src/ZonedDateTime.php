<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;

/**
 * Date-time with a timezone in the ISO-8601 calendar system.
 *
 * Combines {@see LocalDateTime} with {@see ZoneId}, providing full DST awareness.
 * Unlike {@see OffsetDateTime}, the offset can change over time due to DST transitions.
 *
 * Gap and Overlap resolution:
 * - **Gap**: Local time doesn't exist (clocks move forward, e.g., 02:30 → 03:00)
 *   - Default: `SHIFT_FORWARD` - add gap duration
 *   - Alternative: `THROW` - throw exception
 * - **Overlap**: Local time exists twice (clocks move backward, e.g., 02:30 occurs twice)
 *   - Default: `PREFER_EARLIER` - use earlier offset (DST)
 *   - Alternative: `PREFER_LATER` - use later offset (standard time)
 *
 * Example:
 * ```php
 * $zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
 * echo $zdt; // "2024-03-15T14:30:00+01:00[Europe/Warsaw]"
 * ```
 */
final readonly class ZonedDateTime implements \Stringable, TemporalAccessor
{
    private function __construct(
        public LocalDateTime $dateTime,
        public ZoneId $zone,
        public ZoneOffset $offset,
    ) {}

    public function get(TemporalField $field): int|null
    {
        return $this->dateTime->get($field);
    }

    public function supports(TemporalField ...$fields): bool
    {
        return $this->dateTime->supports(...$fields);
    }

    public function query(callable $query): mixed
    {
        return $query($this);
    }

    /**
     * Create ZonedDateTime from components with gap/overlap resolution.
     *
     * @param int<1, 12> $month
     * @param int<1, 31> $day
     * @param int<0, 23> $hour
     * @param int<0, 59> $minute
     * @param int<0, 59> $second
     * @param int<0, 999999999> $nano
     *
     * @throws InvalidDate
     * @throws InvalidTime
     */
    public static function of(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second = 0,
        int $nano = 0,
        ZoneId|null $zone = null,
    ): self {
        $dateTime = LocalDateTime::of($year, $month, $day, $hour, $minute, $second, $nano);
        $zone ??= ZoneId::UTC();

        return self::ofLocal($dateTime, $zone);
    }

    /**
     * Create ZonedDateTime from LocalDateTime and ZoneId.
     *
     * Resolves gaps (SHIFT_FORWARD) and overlaps (PREFER_EARLIER) automatically.
     */
    public static function ofLocal(LocalDateTime $dateTime, ZoneId $zone): self
    {
        // Treat local date-time as if it's in UTC to get epoch second
        $epochDay = $dateTime->date->epochDay;
        $secondOfDay = $dateTime->time->toSecondOfDay();
        $roughUtcTimestamp = $epochDay * 86400 + $secondOfDay;

        // Get offset for this approximate time
        // This gives us a rough estimate - may need refinement for DST boundaries
        $offsetSeconds = $zone->getOffsetForTimestamp($roughUtcTimestamp);

        // Correct the UTC timestamp: local time - offset = UTC
        $correctedUtcTimestamp = $roughUtcTimestamp - $offsetSeconds;

        // Get the actual offset for the corrected UTC time
        $actualOffsetSeconds = $zone->getOffsetForTimestamp($correctedUtcTimestamp);
        $offset = ZoneOffset::ofTotalSeconds($actualOffsetSeconds);

        return new self($dateTime, $zone, $offset);
    }

    /**
     * Create ZonedDateTime from Instant and ZoneId.
     */
    public static function ofInstant(Instant $instant, ZoneId $zone): self
    {
        $offsetSeconds = $zone->getOffsetForTimestamp($instant->epochSecond);
        $offset = ZoneOffset::ofTotalSeconds($offsetSeconds);

        // Convert to OffsetDateTime first, then extract LocalDateTime
        $offsetDateTime = OffsetDateTime::ofInstant($instant, $offset);

        return new self($offsetDateTime->dateTime, $zone, $offset);
    }

    /**
     * Get current ZonedDateTime in given zone (defaults to system timezone).
     */
    public static function now(ZoneId|null $zone = null): self
    {
        $instant = Instant::now();
        $zone ??= ZoneId::systemDefault();

        return self::ofInstant($instant, $zone);
    }

    /**
     * Convert to Instant (UTC point in time).
     */
    public function toInstant(): Instant
    {
        $epochDay = $this->dateTime->date->epochDay;
        $secondOfDay = $this->dateTime->time->toSecondOfDay();
        $epochSecond = $epochDay * 86400 + $secondOfDay;

        $utcEpochSecond = $epochSecond - $this->offset->totalSeconds;

        // Calculate ticks (1 tick = 100ns)
        $ticks = $utcEpochSecond * 10_000_000 + \intdiv($this->dateTime->nano, 100);

        return new Instant($ticks);
    }

    /**
     * Convert to OffsetDateTime (loses timezone information, keeps offset).
     */
    public function toOffsetDateTime(): OffsetDateTime
    {
        return OffsetDateTime::ofDateTimeAndOffset($this->dateTime, $this->offset);
    }

    /**
     * Change timezone while keeping the same instant (UTC timestamp).
     *
     * The local date-time will adjust accordingly.
     *
     * Example:
     * ```php
     * $warsaw = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
     * // 2024-03-15T14:00:00+01:00[Europe/Warsaw]
     *
     * $tokyo = $warsaw->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));
     * // 2024-03-15T22:00:00+09:00[Asia/Tokyo] (same instant, different local time)
     * ```
     */
    public function withZoneSameInstant(ZoneId $newZone): self
    {
        if ($this->zone->equals($newZone)) {
            return $this;
        }

        $instant = $this->toInstant();

        return self::ofInstant($instant, $newZone);
    }

    /**
     * Change timezone while keeping the same local date-time.
     *
     * The instant (UTC timestamp) will change accordingly.
     *
     * Example:
     * ```php
     * $warsaw = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
     * // 2024-03-15T14:00:00+01:00[Europe/Warsaw]
     *
     * $tokyo = $warsaw->withZoneSameLocal(ZoneId::of('Asia/Tokyo'));
     * // 2024-03-15T14:00:00+09:00[Asia/Tokyo] (same local time, different instant)
     * ```
     */
    public function withZoneSameLocal(ZoneId $newZone): self
    {
        if ($this->zone->equals($newZone)) {
            return $this;
        }

        return self::ofLocal($this->dateTime, $newZone);
    }

    /**
     * ISO-8601 extended format with zone: "2024-03-15T14:30:00+02:00[Europe/Warsaw]"
     */
    public function __toString(): string
    {
        return $this->dateTime . (string) $this->offset . '[' . $this->zone . ']';
    }

    /**
     * Check equality with another ZonedDateTime.
     *
     * Two ZonedDateTimes are equal if they represent the same instant
     * (same UTC timestamp), regardless of zone.
     */
    public function equals(self $other): bool
    {
        return $this->toInstant()->epochSecond === $other->toInstant()->epochSecond;
    }

    /**
     * Check if this is before another ZonedDateTime.
     */
    public function isBefore(self $other): bool
    {
        return $this->toInstant()->epochSecond < $other->toInstant()->epochSecond;
    }

    /**
     * Check if this is after another ZonedDateTime.
     */
    public function isAfter(self $other): bool
    {
        return $this->toInstant()->epochSecond > $other->toInstant()->epochSecond;
    }
}
