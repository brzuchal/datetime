<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

/**
 * Common time unit conversions expressed in nanoseconds.
 */
final class TimeUnit
{
    private function __construct()
    {}

    public const int NANOS_PER_SECOND = 1_000_000_000;
    public const int SECONDS_PER_MINUTE = 60;
    public const int MINUTES_PER_HOUR = 60;
    public const int HOURS_PER_DAY = 24;
    public const int NANOS_PER_MINUTE = self::SECONDS_PER_MINUTE * self::NANOS_PER_SECOND;
    public const int NANOS_PER_HOUR = self::MINUTES_PER_HOUR * self::NANOS_PER_MINUTE;
    public const int NANOS_PER_DAY = self::HOURS_PER_DAY * self::NANOS_PER_HOUR;
    public const int SECONDS_PER_HOUR = self::SECONDS_PER_MINUTE * self::MINUTES_PER_HOUR;
    public const int SECONDS_PER_DAY = self::SECONDS_PER_HOUR * self::HOURS_PER_DAY;
    public const int MINUTES_PER_DAY = self::HOURS_PER_DAY * self::MINUTES_PER_HOUR;
}
