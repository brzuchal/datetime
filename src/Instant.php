<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Format\DateTimeFormat;
use Brzuchal\DateTime\Format\DateTimeFormatter;

final class Instant implements \Stringable
{
    public function compareTo(self $other): int
    {
        return $this->ticks <=> $other->ticks;
    }

    public function __toString(): string
    {
        return DateTimeFormatter::of(DateTimeFormat::ExtendedIsoInstant)->format($this);
    }

    public const int TICKS_PER_SECOND = 10_000_000; // 1 tick = 100ns
    public const int TICKS_PER_DAY = self::TICKS_PER_SECOND * 86_400; // 86400s per day
    private const int NANOS_PER_TICK = 100;

    public function __construct(
        public readonly int $ticks, // one tick = 100ns
    ) {}

    /**
     * Get epoch second (number of seconds since 1970-01-01 00:00:00 UTC).
     */
    public function getEpochSecond(): int
    {
        return $this->epochSecond;
    }

    public static function now(): self
    {
        [$fraction, $seconds] = \explode(' ', \microtime());

        $secondsTicks = (int) $seconds * self::TICKS_PER_SECOND;
        $fractionDigits = \substr($fraction, 2);
        $fractionDigits = \substr($fractionDigits, 0, 6);
        $fractionDigits = \str_pad($fractionDigits, 6, '0');
        $microseconds = (int) $fractionDigits;
        $ticks = $secondsTicks + ($microseconds * 10);

        return new self($ticks);
    }

    public static function ofEpochSecond(int $epochSecond, int $nanoAdjustment = 0): self
    {
        $ticks = $epochSecond * self::TICKS_PER_SECOND + \intdiv($nanoAdjustment, 100);

        return new self($ticks);
    }

    public static function of(int|float $timestamp): self
    {
        if (\is_float($timestamp)) {
            $ticks = (int) \floor($timestamp * self::TICKS_PER_SECOND);
        } else {
            $ticks = $timestamp * self::TICKS_PER_SECOND;
        }

        return new self($ticks);
    }

    public int $epochDay {
        get => self::floorDiv($this->ticks, self::TICKS_PER_DAY);
    }

    public int $epochSecond {
        get => self::floorDiv($this->ticks, self::TICKS_PER_SECOND);
    }

    public int $nanoAdjustment {
        get => self::floorMod($this->ticks, self::TICKS_PER_SECOND) * self::NANOS_PER_TICK;
    }

    private static function floorDiv(int $dividend, int $divisor): int
    {
        $quotient = \intdiv($dividend, $divisor);

        if ((($dividend ^ $divisor) < 0) && $dividend % $divisor !== 0) {
            return $quotient - 1;
        }

        return $quotient;
    }

    private static function floorMod(int $dividend, int $divisor): int
    {
        $r = $dividend % $divisor;
        if ((($r ^ $divisor) < 0) && $r !== 0) {
            $r += $divisor;
        }

        return $r;
    }
}
