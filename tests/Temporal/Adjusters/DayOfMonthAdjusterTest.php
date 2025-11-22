<?php declare(strict_types=1);

namespace Tests\Temporal\Adjusters;

use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\Temporal\Adjusters\DayOfMonthAdjuster;
use PHPUnit\Framework\TestCase;

final class DayOfMonthAdjusterTest extends TestCase
{
    public function testFirstDayOfMonth(): void
    {
        $date = LocalDate::of(2024, 6, 18);
        $adjusted = $date->adjust(DayOfMonthAdjuster::first());

        self::assertSame('2024-06-01', (string) $adjusted);
    }

    public function testLastDayOfMonth(): void
    {
        $date = LocalDate::of(2024, 2, 14);
        $adjusted = $date->adjust(DayOfMonthAdjuster::last());

        self::assertSame('2024-02-29', (string) $adjusted);
    }

    public function testLocalDateTimePreservesTime(): void
    {
        $dateTime = LocalDateTime::of(2024, 11, 12, 15, 45, 30);
        $adjusted = $dateTime->adjust(DayOfMonthAdjuster::last());

        self::assertSame('2024-11-30T15:45:30', (string) $adjusted);
    }
}
