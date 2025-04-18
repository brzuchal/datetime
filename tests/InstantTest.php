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

        self::assertEquals(
            \intval(\microtime(true) * 10_000_000 / self::ROUNDING_FACTOR) ,
            \intval($instant->ticks / self::ROUNDING_FACTOR),
        );
    }

    public function testTicks(): void
    {
        $timestamp = \microtime(true);
        $instant = Instant::of($timestamp);
        self::assertEquals(\intval($timestamp * 10_000_000) , $instant->ticks);
    }

    public function testEpochDay(): void
    {
        $timestamp = \microtime(true);
        $instant = Instant::of($timestamp);
        self::assertEquals(\intval($timestamp / (60 * 60 * 24)) , $instant->epochDay);
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
}
