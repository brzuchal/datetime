<?php declare(strict_types=1);

namespace Tests\Calendars;

use Brzuchal\DateTime\CalendarSystems\CalendarSystem;
use Brzuchal\DateTime\CalendarSystems\CalendarSystemRegistry;
use Brzuchal\DateTime\CalendarSystems\IsoCalendarSystem;
use PHPUnit\Framework\TestCase;

final class CalendarSystemRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        CalendarSystemRegistry::reset();
    }

    public function testDefaultContainsIsoCalendar(): void
    {
        self::assertTrue(CalendarSystemRegistry::has('ISO'));
        self::assertArrayHasKey('ISO', CalendarSystemRegistry::all());
    }

    public function testRegisterAddsCustomCalendar(): void
    {
        $custom = new class implements CalendarSystem {
            public function name(): string
            {
                return 'FAKE';
            }

            public function dateFromEpochDay(int $epochDay): array
            {
                return [1970, 1, 1];
            }

            public function epochDayFromDate(int $year, int $month, int $day): int
            {
                return 0;
            }

            public function isLeapYear(int $year): bool
            {
                return false;
            }

            public function isValidDate(int $year, int $month, int $day): bool
            {
                return true;
            }

            public function eraOf(int $year, int $month, int $day): \Brzuchal\DateTime\CalendarSystems\Era
            {
                $iso = new IsoCalendarSystem();

                return $iso->eraOf($year, $month, $day);
            }

            public function monthsInYear(int $year): int
            {
                return 12;
            }

            public function monthLength(int $year, int $month): int
            {
                return 30;
            }

            public function yearLength(int $year): int
            {
                return 360;
            }

            public function dayOfYear(int $year, int $month, int $day): int
            {
                return 1;
            }

            public function yearsMonthsToDays(int $year, int $month, int $day, int $years, int $months): int
            {
                return 0;
            }
        };

        CalendarSystemRegistry::register($custom);

        self::assertTrue(CalendarSystemRegistry::has('FAKE'));
        self::assertArrayHasKey('FAKE', CalendarSystemRegistry::all());
    }

    public function testResetRestoresDefaultRegistry(): void
    {
        CalendarSystemRegistry::register(new class implements CalendarSystem {
            public function name(): string
            {
                return 'TEMP';
            }

            public function dateFromEpochDay(int $epochDay): array
            {
                return [1970, 1, 1];
            }

            public function epochDayFromDate(int $year, int $month, int $day): int
            {
                return 0;
            }

            public function isLeapYear(int $year): bool
            {
                return false;
            }

            public function isValidDate(int $year, int $month, int $day): bool
            {
                return true;
            }

            public function eraOf(int $year, int $month, int $day): \Brzuchal\DateTime\CalendarSystems\Era
            {
                $iso = new IsoCalendarSystem();

                return $iso->eraOf($year, $month, $day);
            }

            public function monthsInYear(int $year): int
            {
                return 12;
            }

            public function monthLength(int $year, int $month): int
            {
                return 30;
            }

            public function yearLength(int $year): int
            {
                return 360;
            }

            public function dayOfYear(int $year, int $month, int $day): int
            {
                return 1;
            }

            public function yearsMonthsToDays(int $year, int $month, int $day, int $years, int $months): int
            {
                return 0;
            }
        });

        CalendarSystemRegistry::reset();

        self::assertTrue(CalendarSystemRegistry::has('ISO'));
        self::assertFalse(CalendarSystemRegistry::has('TEMP'));
    }
}
