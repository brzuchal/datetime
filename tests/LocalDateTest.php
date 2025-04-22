<?php

declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\InvalidInput;
use Brzuchal\DateTime\Temporal\TemporalField;
use Brzuchal\DateTime\InvalidDate;
use Brzuchal\DateTime\LocalDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocalDateTest extends TestCase
{
    private const int SUNDAY_ORD = 6;
    private const int THURSDAY_ORD = 3;

    public function testOfWithRegularDate(): void
    {
        $localDate = LocalDate::of(2023, 5, 10);

        self::assertSame(2023, $localDate->year);
        self::assertSame(5, $localDate->month);
        self::assertSame(10, $localDate->day);
    }

    public function testOfWithLeapYearDate(): void
    {
        $localDate = LocalDate::of(2020, 2, 29);

        self::assertTrue($localDate->isLeapYear);
        self::assertSame(2, $localDate->month);
        self::assertSame(29, $localDate->day);
    }

    public function testFromEpochDay(): void
    {
        // Epoch day: 0 corresponds roughly to 1970-01-01
        $localDate = LocalDate::fromEpochDay(0);

        self::assertSame(1970, $localDate->year);
        self::assertSame(1, $localDate->month);
        self::assertSame(1, $localDate->day);
    }

    public function testDayOfWeekCalculation(): void
    {
        // 1970-01-01 is a Thursday (many consider DayOfWeek enumeration 0-based or 1-based).
        // Depending on the implemented rules, adjust the expected value.
        $localDate = LocalDate::fromEpochDay(0);

        // Example check: If DayOfWeek::Thursday is numeric 3 or 4, adjust accordingly.
        self::assertSame(self::THURSDAY_ORD, $localDate->dayOfWeek);
    }

    // TODO: extend with all args params and desired date (consider mutations passing leap years)
    public function testPlusDays(): void
    {
        $initial = LocalDate::of(2023, 5, 10);
        $result = $initial->plus(days: 5);

        self::assertSame(2023, $result->year);
        self::assertSame(5, $result->month);
        self::assertSame(15, $result->day);
    }

    // TODO: extend with all args params and desired date (consider mutations passing leap years)
    public function testMinusDays(): void
    {
        $initial = LocalDate::of(2023, 5, 10);
        $result = $initial->minus(days: 10);

        self::assertSame(2023, $result->year);
        self::assertSame(4, $result->month);
        // April 30th is 10 days before May 10th
        self::assertSame(30, $result->day);
    }

    public function testParse(): void
    {
        $localDate = LocalDate::parse('2023-05-10');

        self::assertSame(2023, $localDate->year);
        self::assertSame(5, $localDate->month);
        self::assertSame(10, $localDate->day);
    }

    #[DataProvider('dataInvalidDate')]
    public function testParseInvalidDate(string $date): void
    {
        $this->expectException(InvalidDate::class);

        LocalDate::parse($date);
    }

    /**
     * @return iterable<non-empty-string,array{0:non-empty-string}>
     */
    public static function dataInvalidDate(): iterable
    {
        yield '29 Feb in non-leap year' => ['2023-02-29'];
        yield '30 Feb in leap year' => ['2020-02-30'];
        yield '31 April' => ['2023-04-31'];
        yield 'invalid month' => ['2023-13-10'];
        yield 'invalid day' => ['2023-05-32'];
    }

    #[DataProvider('dataParseException')]
    public function testParseException(string $date): void
    {
        $this->expectException(InvalidInput::class);

        LocalDate::parse($date);
    }

    /**
     * @return iterable<non-empty-string,array{0:non-empty-string}>
     */
    public static function dataParseException(): iterable
    {
        yield 'missing day&month' => ['2023'];
        yield 'missing day' => ['2023-05'];
        yield 'doublet day' => ['2023-05-10-10'];
        yield 'missing year' => ['05-10'];
        yield 'additional chars' => ['2023-aa-10'];
    }

    public function testToString(): void
    {
        $localDate = LocalDate::of(2023, 5, 10);

        self::assertSame('2023-05-10', (string) $localDate);
    }

    public function testToStringBigYear(): void
    {
        $localDate = LocalDate::of(12023, 5, 10);

        self::assertSame('+12023-05-10', (string) $localDate);
    }

    public function testToStringNegativeYear(): void
    {
        $localDate = LocalDate::of(-2023, 5, 10);

        self::assertSame('-2023-05-10', (string) $localDate);
    }

    public function testSerialization(): void
    {
        $localDate = LocalDate::of(2023, 5, 10);

        $restoredLocalDate = unserialize(serialize($localDate));
        self::assertInstanceOf(LocalDate::class, $restoredLocalDate);
        self::assertEquals($localDate->year, $restoredLocalDate->year);
        self::assertEquals($localDate->month, $restoredLocalDate->month);
        self::assertEquals($localDate->day, $restoredLocalDate->day);
    }

    public function testGetTemporalField(): void
    {
        $localDate = LocalDate::of(2025, 3, 23);

        self::assertEquals(23, $localDate->get(TemporalField::Day));
        self::assertEquals(3, $localDate->get(TemporalField::Month));
        self::assertEquals(2025, $localDate->get(TemporalField::Year));
        self::assertEquals(self::SUNDAY_ORD, $localDate->get(TemporalField::DayOfWeek));
        self::assertEquals(82, $localDate->get(TemporalField::DayOfYear));
        self::assertSame(12, $localDate->weekOfYear);
        self::assertSame(3, $localDate->weekOfMonth);
    }

    public function testWeekOfYear(): void
    {
        $localDate = LocalDate::of(2025, 3, 23);

        self::assertSame(12, $localDate->weekOfYear);
    }

    public function testWeekOfMonth(): void
    {
        $localDate = LocalDate::of(2025, 3, 23);

        self::assertSame(3, $localDate->weekOfMonth);
    }

    public function testIsLeapYear(): void
    {
        $localDate = LocalDate::of(2020, 2, 29);

        self::assertTrue($localDate->isLeapYear);
    }

    public function testIsNotLeapYear(): void
    {
        $localDate = LocalDate::of(2023, 2, 28);

        self::assertFalse($localDate->isLeapYear);
    }

    #[DataProvider('dateAdditionProvider')]
    public function testAdd(string $startDate, int $years, int $months, int $days, string $expectedDate): void
    {
        $minus = \str_starts_with($expectedDate, '-');
        if ($minus) {
            [$_, $expectedYear, $expectedMonth, $expectedDay] = \explode('-', $expectedDate);
            $expectedYear = -$expectedYear;
        } else {
            [$expectedYear, $expectedMonth, $expectedDay] = \explode('-', $expectedDate);
        }

        $date = LocalDate::parse($startDate);
        $result = $date->add(new Duration($years, $months, $days));

        self::assertSame((int) $expectedYear, $result->year);
        self::assertSame((int) $expectedMonth, $result->month);
        self::assertSame((int) $expectedDay, $result->day);
    }

    public static function dateAdditionProvider(): array
    {
        return [
            // Basic date increment tests
            ['2024-01-01', 1, 2, 10, '2025-03-11'],  // Adding year, month, days
            ['2024-02-28', 0, 0, 1, '2024-02-29'],  // Leap year check

            // Leap year transition
            ['2024-02-29', 1, 0, 0, '2025-02-28'],  // Leap year to non-leap year
            ['2024-02-29', 4, 0, 0, '2028-02-29'],  // Leap year to another leap year

            // Month overflow tests
            ['2024-11-30', 0, 3, 0, '2025-02-28'],  // Crossing into February
            ['2024-12-31', 0, 1, 0, '2025-01-31'],  // Handling December month rollover

            // Day overflow within month
            ['2024-03-31', 0, 1, 0, '2024-04-30'],  // March 31 + 1 month → April 30
            ['2024-04-30', 0, -1, 0, '2024-03-30'], // April 30 - 1 month → March 30

            // Negative duration handling
            ['2024-03-15', -1, -2, -40, '2022-12-06'],  // Large backtracking

            // Testing exact month-day relations
            ['2024-08-31', 0, 1, 0, '2024-09-30'],  // August 31 → September adjustment
            ['2024-10-31', 0, 1, 0, '2024-11-30'],  // October 31 → November adjustment

            // Extreme values
            ['9999-12-31', 0, 1, 1, '10000-02-01'], // Large future test
            ['0001-01-01', -1, -1, -1, '-0001-11-30'], // Large negative test
        ];
    }
}
