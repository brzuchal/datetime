<?php

namespace Tests;

use Brzuchal\DateTime\Instant;
use PHPUnit\Framework\TestCase;

final class InstantTest extends TestCase
{
    private const int ROUNDING_FACTOR = 100; // 10us

    public function testNow(): void
    {
        $instant = Instant::now();
        $timestamp = \microtime(true);
        $expected = (int) ($timestamp * 10_000_000 / self::ROUNDING_FACTOR);
        $actual = (int) ($instant->ticks / self::ROUNDING_FACTOR);

        // Allow a small difference to account for system timing uncertainties
        self::assertEqualsWithDelta($expected, $actual, 1, 'Ticks differ more than expected');
    }

    public function testTicks(): void
    {
        $timestamp = \microtime(true);
        $instant = Instant::of($timestamp);
        self::assertEquals(\intval($timestamp * 10_000_000), $instant->ticks);
    }

    public function testEpochDay(): void
    {
        $timestamp = \microtime(true);
        $instant = Instant::of($timestamp);
        self::assertEquals(\intval($timestamp / (60 * 60 * 24)), $instant->epochDay);
    }

    public function testEpochSecond(): void
    {
        $timestamp = \microtime(true);
        $instant = Instant::of($timestamp);
        self::assertEquals((int) $timestamp, $instant->epochSecond);
    }

    public function testNanoAdjustment(): void
    {
        $timestamp = \microtime(true);
        $instant = Instant::of($timestamp);
        self::assertEquals(($timestamp * 10_000_000) % 10_000_000 * 100, $instant->nanoAdjustment);
    }

    public function testNegativeTicksProduceFloorEpochSecond(): void
    {
        $instant = new Instant(-1);

        self::assertSame(-1, $instant->epochSecond);
        self::assertSame(999_999_900, $instant->nanoAdjustment);
    }

    public function testNegativeTicksProduceFloorEpochDay(): void
    {
        $instant = new Instant(-1);

        self::assertSame(-1, $instant->epochDay);
    }

    public function testOfHandlesNegativeTimestamp(): void
    {
        $instant = Instant::of(-0.5);

        self::assertSame(-1, $instant->epochSecond);
        self::assertSame(500_000_000, $instant->nanoAdjustment);
    }
}
