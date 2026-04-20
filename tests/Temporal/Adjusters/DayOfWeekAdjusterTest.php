<?php

declare(strict_types=1);

namespace Tests\Temporal\Adjusters;

use Brzuchal\DateTime\DayOfWeek;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\Temporal\Adjusters\DayOfWeekAdjuster;
use PHPUnit\Framework\TestCase;

final class DayOfWeekAdjusterTest extends TestCase
{
    public function testNextReturnsFollowingOccurrence(): void
    {
        $date = LocalDate::of(2024, 6, 12); // Wednesday
        $adjusted = $date->adjust(DayOfWeekAdjuster::next(DayOfWeek::Friday));

        self::assertSame('2024-06-14', (string) $adjusted);
    }

    public function testNextOrSameReturnsSameWhenAlreadyMatching(): void
    {
        $date = LocalDate::of(2024, 6, 17); // Monday
        $adjusted = $date->adjust(DayOfWeekAdjuster::nextOrSame(DayOfWeek::Monday));

        self::assertSame($date->year, $adjusted->year);
        self::assertSame($date->month, $adjusted->month);
        self::assertSame($date->day, $adjusted->day);
    }

    public function testPreviousReturnsEarlierOccurrence(): void
    {
        $date = LocalDate::of(2024, 6, 12); // Wednesday
        $adjusted = $date->adjust(DayOfWeekAdjuster::previous(DayOfWeek::Monday));

        self::assertSame('2024-06-10', (string) $adjusted);
    }

    public function testPreviousOrSameKeepsCurrentWhenMatching(): void
    {
        $date = LocalDate::of(2024, 6, 10); // Monday
        $adjusted = $date->adjust(DayOfWeekAdjuster::previousOrSame(DayOfWeek::Monday));

        self::assertSame((string) $date, (string) $adjusted);
    }

    public function testAdjustDateTimeKeepsTimeComponent(): void
    {
        $dateTime = LocalDateTime::of(2024, 6, 12, 8, 30);
        $adjusted = $dateTime->adjust(DayOfWeekAdjuster::next(DayOfWeek::Friday));

        self::assertSame('2024-06-14T08:30:00', (string) $adjusted);
    }

    public function testAdjusterRejectsUnsupportedTemporal(): void
    {
        $adjuster = DayOfWeekAdjuster::next(DayOfWeek::Friday);

        $this->expectException(\InvalidArgumentException::class);
        $adjuster->adjust($this->createMock(\Brzuchal\DateTime\Temporal\Temporal::class));
    }
}
