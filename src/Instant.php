<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

final class Instant
{
    public const int TICKS_PER_SECOND = 10_000_000; // 1 tick = 100ns
    public const int TICKS_PER_DAY = self::TICKS_PER_SECOND * 86_400; // 86400s per day

    public function __construct(
        public readonly int $ticks, // one tick = 100ns
    ) {}

    public static function now(): self
    {
        [$frac, $sec] = \explode(' ', \microtime());

        return new self(((int) $sec) * self::TICKS_PER_SECOND + (int) \substr($frac, 2, 7));
    }

    public static function of(int|float $timestamp): self
    {
        return new self(\intval($timestamp * self::TICKS_PER_SECOND));
    }

    public int $epochDay {
        get => \intdiv($this->ticks, self::TICKS_PER_DAY);
    }

    public int $epochSecond {
        get => \intdiv($this->ticks, self::TICKS_PER_SECOND);
    }

    public int $nanoAdjustment {
        get => ($this->ticks % self::TICKS_PER_SECOND) * 100; // 100ns per tick
    }
}
