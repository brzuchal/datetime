<?php

declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\DayOfWeek;
use Brzuchal\DateTime\Format\InvalidInput;
use Brzuchal\DateTime\Format\TemporalField;
use Brzuchal\DateTime\InvalidDate;
use Brzuchal\DateTime\LocalDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LocalDateTest extends TestCase
{
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
        self::assertSame(DayOfWeek::Thursday, $localDate->dayOfWeek);
    }

    public function testPlusDays(): void
    {
        $initial = LocalDate::of(2023, 5, 10);
        $result = $initial->plusDays(5);

        self::assertSame(2023, $result->year);
        self::assertSame(5, $result->month);
        self::assertSame(15, $result->day);
    }

    public function testMinusDays(): void
    {
        $initial = LocalDate::of(2023, 5, 10);
        $result = $initial->minusDays(10);

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
        self::assertEquals(DayOfWeek::Sunday->value, $localDate->get(TemporalField::DayOfWeek));
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
}
