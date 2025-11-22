<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Stringable;

/**
 * A time-zone offset from Greenwich/UTC, such as +02:00.
 *
 * This is a fixed offset from UTC/Greenwich in seconds.
 * A ZoneOffset instance is immutable and thread-safe.
 */
final class ZoneOffset implements Stringable
{
    /** @var int Minimum offset in seconds (-18 hours) */
    public const MIN_SECONDS = -64800;

    /** @var int Maximum offset in seconds (+18 hours) */
    public const MAX_SECONDS = 64800;

    /** @var array<int, self> Cache for common offsets */
    private static array $cache = [];

    /** @var self UTC offset (zero) */
    private static ?self $utc = null;

    /**
     * @param int $totalSeconds Total offset in seconds (-64800 to +64800)
     */
    private function __construct(
        public readonly int $totalSeconds,
    ) {
        if ($totalSeconds < self::MIN_SECONDS || $totalSeconds > self::MAX_SECONDS) {
            throw new InvalidOffset(sprintf(
                'Zone offset not in valid range: %d seconds (must be between %d and %d)',
                $totalSeconds,
                self::MIN_SECONDS,
                self::MAX_SECONDS
            ));
        }
    }

    /**
     * Obtains an instance of ZoneOffset using the total offset in seconds.
     *
     * @param int $totalSeconds The total time-zone offset in seconds, from -64800 to +64800
     * @return self The ZoneOffset
     * @throws InvalidOffset If the offset is not in the required range
     */
    public static function ofTotalSeconds(int $totalSeconds): self
    {
        // Return cached instance for common offsets
        if (isset(self::$cache[$totalSeconds])) {
            return self::$cache[$totalSeconds];
        }

        $offset = new self($totalSeconds);

        // Cache offsets in 15-minute increments from -12:00 to +14:00
        if ($totalSeconds >= -43200 && $totalSeconds <= 50400 && $totalSeconds % 900 === 0) {
            self::$cache[$totalSeconds] = $offset;
        }

        return $offset;
    }

    /**
     * Obtains an instance of ZoneOffset using an offset in hours, minutes and seconds.
     *
     * @param int $hours The time-zone offset in hours, from -18 to +18
     * @param int $minutes The time-zone offset in minutes, from 0 to ±59
     * @param int $seconds The time-zone offset in seconds, from 0 to ±59
     * @return self The ZoneOffset
     * @throws InvalidOffset If the offset is not in the required range
     */
    public static function of(int $hours, int $minutes = 0, int $seconds = 0): self
    {
        if ($hours < -18 || $hours > 18) {
            throw new InvalidOffset(sprintf('Zone offset hours not in valid range: %d', $hours));
        }

        if ($hours > 0) {
            if ($minutes < 0 || $seconds < 0) {
                throw new InvalidOffset('Zone offset minutes and seconds must be positive for positive hours');
            }
        } elseif ($hours < 0) {
            if ($minutes > 0 || $seconds > 0) {
                throw new InvalidOffset('Zone offset minutes and seconds must be negative or zero for negative hours');
            }
        }

        if ($minutes < -59 || $minutes > 59) {
            throw new InvalidOffset(sprintf('Zone offset minutes not in valid range: %d', $minutes));
        }

        if ($seconds < -59 || $seconds > 59) {
            throw new InvalidOffset(sprintf('Zone offset seconds not in valid range: %d', $seconds));
        }

        $totalSeconds = $hours * 3600 + $minutes * 60 + $seconds;

        return self::ofTotalSeconds($totalSeconds);
    }

    /**
     * Obtains an instance of ZoneOffset from a text string such as +02:00.
     *
     * Accepts formats: Z, +HH:MM, +HH:MM:SS, +HHMM, +HH
     *
     * @param string $offsetId The offset ID, not null
     * @return self The ZoneOffset
     * @throws InvalidOffset If the offset ID is invalid
     */
    public static function parse(string $offsetId): self
    {
        if ($offsetId === 'Z' || $offsetId === '+00:00' || $offsetId === '+00') {
            return self::utc();
        }

        if (!preg_match('/^([+-])(\d{2})(?::?(\d{2}))?(?::?(\d{2}))?$/', $offsetId, $matches)) {
            throw new InvalidOffset(sprintf('Invalid zone offset format: %s', $offsetId));
        }

        $sign = $matches[1];
        $hours = (int) $matches[2];
        $minutes = isset($matches[3]) ? (int) $matches[3] : 0;
        $seconds = isset($matches[4]) ? (int) $matches[4] : 0;

        if ($sign === '-') {
            $hours = -$hours;
            $minutes = -$minutes;
            $seconds = -$seconds;
        }

        return self::of($hours, $minutes, $seconds);
    }

    /**
     * Gets the ZoneOffset for UTC (zero offset).
     *
     * @return self The UTC offset
     */
    public static function utc(): self
    {
        return self::$utc ??= self::ofTotalSeconds(0);
    }

    /**
     * Outputs this offset as a String, using the normalized ID.
     *
     * @return string The offset ID, such as 'Z' or '+02:00'
     */
    public function __toString(): string
    {
        if ($this->totalSeconds === 0) {
            return 'Z';
        }

        $absSeconds = abs($this->totalSeconds);
        $hours = intdiv($absSeconds, 3600);
        $minutes = intdiv($absSeconds % 3600, 60);
        $seconds = $absSeconds % 60;

        $sign = $this->totalSeconds < 0 ? '-' : '+';

        if ($seconds === 0) {
            return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
        }

        return sprintf('%s%02d:%02d:%02d', $sign, $hours, $minutes, $seconds);
    }

    /**
     * Compares this offset to another offset.
     *
     * @param self $other The other offset to compare to
     * @return int Negative if this offset is less, positive if greater, zero if equal
     */
    public function compareTo(self $other): int
    {
        return $this->totalSeconds <=> $other->totalSeconds;
    }

    /**
     * Checks if this offset equals another offset.
     *
     * @param self $other The other offset
     * @return bool True if the offsets are equal
     */
    public function equalTo(self $other): bool
    {
        return $this->totalSeconds === $other->totalSeconds;
    }
}
