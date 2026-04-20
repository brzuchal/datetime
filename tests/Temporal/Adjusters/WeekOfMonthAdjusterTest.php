<?php

declare(strict_types=1);

namespace Tests\Temporal\Adjusters;

use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\Temporal\Adjusters\WeekOfMonthAdjuster;
use PHPUnit\Framework\TestCase;

final class WeekOfMonthAdjusterTest extends TestCase
{
    public function testFirstWeekReturnsFirstMondayInsideMonth(): void
    {
        $date = LocalDate::of(2024, 6, 18); // Tuesday
        $adjusted = $date->adjust(WeekOfMonthAdjuster::firstWeek());

        self::assertSame('2024-06-03', (string) $adjusted);
    }

    public function testLastWeekReturnsWeekContainingMonthEnd(): void
    {
        $date = LocalDate::of(2024, 6, 5);
        $adjusted = $date->adjust(WeekOfMonthAdjuster::lastWeek());

        self::assertSame('2024-06-24', (string) $adjusted);
    }

    public function testNextWeekMovesForwardWithinMonth(): void
    {
        $date = LocalDate::of(2024, 6, 4);
        $adjusted = $date->adjust(WeekOfMonthAdjuster::nextWeek());

        self::assertSame('2024-06-10', (string) $adjusted);
    }

    public function testNextWeekClampsToLastWeekWhenCrossingMonth(): void
    {
        $date = LocalDate::of(2024, 6, 27);
        $adjusted = $date->adjust(WeekOfMonthAdjuster::nextWeek());

        self::assertSame('2024-06-24', (string) $adjusted);
    }

    public function testPreviousWeekMovesBackwardWithinMonth(): void
    {
        $date = LocalDate::of(2024, 6, 11);
        $adjusted = $date->adjust(WeekOfMonthAdjuster::previousWeek());

        self::assertSame('2024-06-03', (string) $adjusted);
    }

    public function testPreviousWeekClampsToFirstWeek(): void
    {
        $date = LocalDate::of(2024, 6, 3);
        $adjusted = $date->adjust(WeekOfMonthAdjuster::previousWeek());

        self::assertSame('2024-06-03', (string) $adjusted);
    }

    public function testDateTimePreservesTime(): void
    {
        $dateTime = LocalDateTime::of(2024, 6, 12, 8, 30);
        $adjusted = $dateTime->adjust(WeekOfMonthAdjuster::previousWeek());

        self::assertSame('2024-06-03T08:30:00', (string) $adjusted);
    }
}
