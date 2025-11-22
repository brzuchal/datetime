<?php

declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\DayOfWeek;
use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Period;
use Brzuchal\DateTime\Format\InvalidInput;
use Brzuchal\DateTime\Format\TemporalFields;
use Brzuchal\DateTime\InsufficientDateComponents;
use Brzuchal\DateTime\InvalidDate;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\Temporal\Adjusters\DayOfWeekAdjuster;
use Brzuchal\DateTime\Temporal\TemporalField;
use Brzuchal\DateTime\Temporal\TemporalQueries;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

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

    /**
     * TODO: extend with all args params and desired date (consider mutations passing leap years)
     */
    public function testPlusDays(): void
    {
        $initial = LocalDate::of(2023, 5, 10);
        $result = $initial->plus(days: 5);

        self::assertSame(2023, $result->year);
        self::assertSame(5, $result->month);
        self::assertSame(15, $result->day);
    }

    /**
     * TODO: extend with all args params and desired date (consider mutations passing leap years)
     */
    public function testMinusDays(): void
    {
        $initial = LocalDate::of(2023, 5, 10);
        $result = $initial->minus(days: 10);

        self::assertSame(2023, $result->year);
        self::assertSame(4, $result->month);
        // April 30th is 10 days before May 10th
        self::assertSame(30, $result->day);
    }

    public function testMinusDaysCrossesYearBoundary(): void
    {
        $initial = LocalDate::of(2024, 1, 1);
        $result = $initial->minus(days: 1);

        self::assertSame(2023, $result->year);
        self::assertSame(12, $result->month);
        self::assertSame(31, $result->day);
    }

    public function testMinusMonthsMovesBackwardAcrossYearBoundary(): void
    {
        $initial = LocalDate::of(2024, 1, 31);
        $result = $initial->minus(months: 1);

        self::assertSame(2023, $result->year);
        self::assertSame(12, $result->month);
        self::assertSame(31, $result->day);
    }

    public function testMinusYearsPreservesMonthAndDay(): void
    {
        $initial = LocalDate::of(2020, 3, 15);
        $result = $initial->minus(years: 1);

        self::assertSame(2019, $result->year);
        self::assertSame(3, $result->month);
        self::assertSame(15, $result->day);
    }

    public function testMinusWithMixedComponents(): void
    {
        $initial = LocalDate::of(2024, 3, 31);
        $result = $initial->minus(years: 1, months: 1, days: 5);

        self::assertSame(2023, $result->year);
        self::assertSame(2, $result->month);
        self::assertSame(23, $result->day);
    }


    public function testAddMonthsSkipsYearZero(): void
    {
        $initial = LocalDate::of(-1, 12, 31);
        $result = $initial->plusPeriod(new Period(months: 2));

        self::assertSame(1, $result->year);
    }

    public function testAddNegativeMonthsSkipsYearZero(): void
    {
        $initial = LocalDate::of(1, 1, 15);
        $result = $initial->plusPeriod(new Period(months: -2));

        self::assertSame(-1, $result->year);
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

    public function testUnserializeRejectsMissingDate(): void
    {
        $class = LocalDate::class;
        $payload = 'O:' . \strlen($class) . ':"' . $class . '":0:{}';

        $this->expectException(UnexpectedValueException::class);

        unserialize($payload, ['allowed_classes' => [LocalDate::class]]);
    }

    public function testUnserializeRejectsMalformedDate(): void
    {
        $class = LocalDate::class;
        $payload = 'O:' . \strlen($class) . ':"' . $class . '":1:{s:4:"date";s:10:"2023-0a-10";}';

        $this->expectException(UnexpectedValueException::class);

        unserialize($payload, ['allowed_classes' => [LocalDate::class]]);
    }

    public function testUnserializeRejectsNegativeMonth(): void
    {
        $class = LocalDate::class;
        $payload = 'O:' . \strlen($class) . ':"' . $class . '":1:{s:4:"date";s:10:"2023--5-10";}';

        $this->expectException(UnexpectedValueException::class);

        unserialize($payload, ['allowed_classes' => [LocalDate::class]]);
    }

    public function testUnserializeRejectsInvalidIsoDate(): void
    {
        $class = LocalDate::class;
        $payload = 'O:' . \strlen($class) . ':"' . $class . '":1:{s:4:"date";s:10:"2023-02-30";}';

        $this->expectException(UnexpectedValueException::class);

        unserialize($payload, ['allowed_classes' => [LocalDate::class]]);
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
        self::assertSame(4, $localDate->weekOfMonth);
    }

    public function testWeekOfYear(): void
    {
        $localDate = LocalDate::of(2025, 3, 23);

        self::assertSame(12, $localDate->weekOfYear);
    }

    public function testWeekOfYearIsoBoundaries(): void
    {
        self::assertSame(53, LocalDate::of(2021, 1, 1)->weekOfYear);
        self::assertSame(1, LocalDate::of(2021, 1, 4)->weekOfYear);
        self::assertSame(52, LocalDate::of(2022, 1, 1)->weekOfYear);
    }

    public function testWeekOfMonth(): void
    {
        $localDate = LocalDate::of(2025, 3, 23);

        self::assertSame(4, $localDate->weekOfMonth);
    }

    public function testWeekOfMonthAcrossBoundary(): void
    {
        self::assertSame(1, LocalDate::of(2021, 5, 1)->weekOfMonth);
        self::assertSame(2, LocalDate::of(2021, 5, 3)->weekOfMonth);
        self::assertSame(6, LocalDate::of(2021, 5, 31)->weekOfMonth);
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
            $expectedYear = -(int) $expectedYear;
        } else {
            [$expectedYear, $expectedMonth, $expectedDay] = \explode('-', $expectedDate);
        }

        $date = LocalDate::parse($startDate);
        $result = $date->plusPeriod(new Period($years, $months, $days));

        self::assertSame((int) $expectedYear, $result->year);
        self::assertSame((int) $expectedMonth, $result->month);
        self::assertSame((int) $expectedDay, $result->day);
    }

    /**
     * @return array<int, array{0: string, 1: int, 2: int, 3: int, 4: string}>
     */
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

    public function testSerializeRoundTripReturnsIdenticalDate(): void
    {
        $date = LocalDate::of(2024, 7, 14);

        $restored = unserialize(serialize($date));

        self::assertInstanceOf(LocalDate::class, $restored);
        self::assertSame(2024, $restored->year);
        self::assertSame(7, $restored->month);
        self::assertSame(14, $restored->day);
    }

    public function testRequiredFieldsListsYearMonthDay(): void
    {
        self::assertSame(
            [TemporalField::Year, TemporalField::Month, TemporalField::Day],
            LocalDate::requires(),
        );
    }

    public function testFromTemporalAccessor(): void
    {
        $fields = new TemporalFields(year: 2024, month: 5, day: 17);

        $date = LocalDate::from($fields);

        self::assertSame(2024, $date->year);
        self::assertSame(5, $date->month);
        self::assertSame(17, $date->day);
    }

    public function testFromTemporalAccessorMissingFieldThrows(): void
    {
        $fields = new TemporalFields(year: 2024, month: 5);

        $this->expectException(InsufficientDateComponents::class);

        LocalDate::from($fields);
    }

    public function testQueryReturnsLocalDate(): void
    {
        $fields = new TemporalFields(year: 2024, month: 11, day: 5);

        $date = $fields->query(TemporalQueries::localDate());

        self::assertInstanceOf(LocalDate::class, $date);
        self::assertSame(2024, $date->year);
        self::assertSame(11, $date->month);
        self::assertSame(5, $date->day);
    }

    public function testQueryReturnsNullWhenMissingFields(): void
    {
        $fields = new TemporalFields(year: 2024);

        self::assertNull($fields->query(TemporalQueries::localDate()));
    }

    public function testAdjustNextOrSameMonday(): void
    {
        $friday = LocalDate::of(2024, 6, 14); // Friday
        $adjusted = $friday->adjust(DayOfWeekAdjuster::nextOrSame(DayOfWeek::Monday));

        self::assertSame(2024, $adjusted->year);
        self::assertSame(6, $adjusted->month);
        self::assertSame(17, $adjusted->day);
    }

    public function testAdjustNextMondayStrict(): void
    {
        $monday = LocalDate::of(2024, 6, 17);
        $adjusted = $monday->adjust(DayOfWeekAdjuster::next(DayOfWeek::Monday));

        self::assertSame('2024-06-24', (string) $adjusted);
    }

    public function testUnserializeRejectsInvalidIsoPayload(): void
    {
        $date = (new \ReflectionClass(LocalDate::class))->newInstanceWithoutConstructor();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Serialized Brzuchal\\DateTime\\LocalDate month must be between 1 and 12.');

        $date->__unserialize(['value' => '2024-13-15']);
    }

    public function testCompareToOrdersByEpochDay(): void
    {
        $first = LocalDate::of(2024, 7, 14);
        $second = LocalDate::of(2024, 7, 15);

        self::assertSame(-1, $first->compareTo($second));
        self::assertSame(1, $second->compareTo($first));
        self::assertSame(0, $first->compareTo(LocalDate::of(2024, 7, 14)));
    }

    public function testEqualToChecksIsoComponents(): void
    {
        $first = LocalDate::of(2024, 7, 14);

        self::assertTrue($first->equalTo(LocalDate::of(2024, 7, 14)));
        self::assertFalse($first->equalTo(LocalDate::of(2024, 7, 15)));
    }

    // Period integration tests

    public function testPlusPeriodAddsYearsMonthsDays(): void
    {
        $date = LocalDate::of(2025, 1, 15);
        $period = Period::of(years: 1, months: 2, days: 10);

        $result = $date->plusPeriod($period);

        self::assertSame(2026, $result->year);
        self::assertSame(3, $result->month);
        self::assertSame(25, $result->day);
    }

    public function testPlusPeriodWithZeroPeriodReturnsSameInstance(): void
    {
        $date = LocalDate::of(2025, 1, 15);
        $period = Period::zero();

        $result = $date->plusPeriod($period);

        self::assertSame($date, $result);
    }

    public function testPlusPeriodHandlesMonthEndOverflow(): void
    {
        $date = LocalDate::of(2025, 1, 31);
        $period = Period::ofMonths(1);

        $result = $date->plusPeriod($period);

        // January 31 + 1 month → February 28 (not a leap year)
        self::assertSame(2025, $result->year);
        self::assertSame(2, $result->month);
        self::assertSame(28, $result->day);
    }

    public function testPlusPeriodWithLeapYearTransition(): void
    {
        $date = LocalDate::of(2024, 2, 29);  // leap year
        $period = Period::ofYears(1);

        $result = $date->plusPeriod($period);

        // February 29, 2024 + 1 year → February 28, 2025 (not a leap year)
        self::assertSame(2025, $result->year);
        self::assertSame(2, $result->month);
        self::assertSame(28, $result->day);
    }

    public function testMinusPeriodSubtractsYearsMonthsDays(): void
    {
        $date = LocalDate::of(2026, 3, 25);
        $period = Period::of(years: 1, months: 2, days: 10);

        $result = $date->minusPeriod($period);

        self::assertSame(2025, $result->year);
        self::assertSame(1, $result->month);
        self::assertSame(15, $result->day);
    }

    public function testMinusPeriodWithZeroPeriodReturnsSameInstance(): void
    {
        $date = LocalDate::of(2025, 1, 15);
        $period = Period::zero();

        $result = $date->minusPeriod($period);

        self::assertSame($date, $result);
    }

    public function testPlusPeriodCrossesYearBoundary(): void
    {
        $date = LocalDate::of(2024, 12, 15);
        $period = Period::of(months: 2, days: 20);

        $result = $date->plusPeriod($period);

        // December 15 + 2 months = February 15, then + 20 days = March 7
        self::assertSame(2025, $result->year);
        self::assertSame(3, $result->month);
        self::assertSame(7, $result->day);
    }

    public function testMinusPeriodCrossesYearBoundary(): void
    {
        $date = LocalDate::of(2025, 1, 10);
        $period = Period::of(months: 2, days: 15);

        $result = $date->minusPeriod($period);

        self::assertSame(2024, $result->year);
        self::assertSame(10, $result->month);
        self::assertSame(26, $result->day);
    }
}

