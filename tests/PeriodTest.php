<?php

declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\InvalidDuration;
use Brzuchal\DateTime\Period;
use PHPUnit\Framework\TestCase;

final class PeriodTest extends TestCase
{
    public function testOfCreatesAPeriod(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);

        self::assertSame(1, $period->years);
        self::assertSame(2, $period->months);
        self::assertSame(3, $period->days);
    }

    public function testOfYearsCreatesAPeriodWithOnlyYears(): void
    {
        $period = Period::ofYears(5);

        self::assertSame(5, $period->years);
        self::assertSame(0, $period->months);
        self::assertSame(0, $period->days);
    }

    public function testOfMonthsCreatesAPeriodWithOnlyMonths(): void
    {
        $period = Period::ofMonths(8);

        self::assertSame(0, $period->years);
        self::assertSame(8, $period->months);
        self::assertSame(0, $period->days);
    }

    public function testOfDaysCreatesAPeriodWithOnlyDays(): void
    {
        $period = Period::ofDays(15);

        self::assertSame(0, $period->years);
        self::assertSame(0, $period->months);
        self::assertSame(15, $period->days);
    }

    public function testZeroCreatesAZeroPeriod(): void
    {
        $period = Period::zero();

        self::assertSame(0, $period->years);
        self::assertSame(0, $period->months);
        self::assertSame(0, $period->days);
        self::assertTrue($period->isZero());
    }

    public function testIsZeroReturnsTrueForZeroPeriod(): void
    {
        $period = new Period();

        self::assertTrue($period->isZero());
    }

    public function testIsZeroReturnsFalseForNonZeroPeriod(): void
    {
        self::assertFalse(Period::ofYears(1)->isZero());
        self::assertFalse(Period::ofMonths(1)->isZero());
        self::assertFalse(Period::ofDays(1)->isZero());
    }

    public function testIsNegativeReturnsTrueWhenAnyUnitIsNegative(): void
    {
        self::assertTrue(Period::of(years: -1)->isNegative());
        self::assertTrue(Period::of(months: -1)->isNegative());
        self::assertTrue(Period::of(days: -1)->isNegative());
        self::assertTrue(Period::of(years: 1, months: -1)->isNegative());
    }

    public function testIsNegativeReturnsFalseWhenAllUnitsAreNonNegative(): void
    {
        self::assertFalse(Period::zero()->isNegative());
        self::assertFalse(Period::of(years: 1, months: 2, days: 3)->isNegative());
    }

    public function testPlusPeriod(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $other = Period::of(years: 2, months: 3, days: 4);
        $result = $period->plusPeriod($other);

        self::assertSame(3, $result->years);
        self::assertSame(5, $result->months);
        self::assertSame(7, $result->days);
    }

    public function testPlusPeriodWithZero(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $result = $period->plusPeriod(Period::zero());

        self::assertSame($period, $result);
    }

    public function testPlusScalar(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $result = $period->plus(years: 2, months: 3, days: 4);

        self::assertSame(3, $result->years);
        self::assertSame(5, $result->months);
        self::assertSame(7, $result->days);
    }

    public function testPlusScalarZeroReturnsSameInstance(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $result = $period->plus(years: 0, months: 0, days: 0);

        self::assertSame($period, $result);
    }

    public function testPlusYearsAddsYears(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $result = $period->plusYears(5);

        self::assertSame(6, $result->years);
        self::assertSame(2, $result->months);
        self::assertSame(3, $result->days);
    }

    public function testPlusYearsWithZeroReturnsSameInstance(): void
    {
        $period = Period::ofYears(1);
        $result = $period->plusYears(0);

        self::assertSame($period, $result);
    }

    public function testPlusMonthsAddsMonths(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $result = $period->plusMonths(5);

        self::assertSame(1, $result->years);
        self::assertSame(7, $result->months);
        self::assertSame(3, $result->days);
    }

    public function testPlusDaysAddsDays(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $result = $period->plusDays(10);

        self::assertSame(1, $result->years);
        self::assertSame(2, $result->months);
        self::assertSame(13, $result->days);
    }

    public function testMinusSubtractsAnotherPeriod(): void
    {
        $period1 = Period::of(years: 5, months: 6, days: 7);
        $period2 = Period::of(years: 2, months: 3, days: 4);

        $result = $period1->minusPeriod($period2);

        self::assertSame(3, $result->years);
        self::assertSame(3, $result->months);
        self::assertSame(3, $result->days);
    }

    public function testMinusYearsSubtractsYears(): void
    {
        $period = Period::of(years: 5, months: 2, days: 3);
        $result = $period->minusYears(2);

        self::assertSame(3, $result->years);
        self::assertSame(2, $result->months);
        self::assertSame(3, $result->days);
    }

    public function testMinusMonthsSubtractsMonths(): void
    {
        $period = Period::of(years: 1, months: 6, days: 3);
        $result = $period->minusMonths(2);

        self::assertSame(1, $result->years);
        self::assertSame(4, $result->months);
        self::assertSame(3, $result->days);
    }

    public function testMinusDaysSubtractsDays(): void
    {
        $period = Period::of(years: 1, months: 2, days: 10);
        $result = $period->minusDays(5);

        self::assertSame(1, $result->years);
        self::assertSame(2, $result->months);
        self::assertSame(5, $result->days);
    }

    public function testNegatedNegatesAllComponents(): void
    {
        $period = Period::of(years: 1, months: 2, days: 3);
        $result = $period->negated();

        self::assertSame(-1, $result->years);
        self::assertSame(-2, $result->months);
        self::assertSame(-3, $result->days);
    }

    public function testNegatedOnNegativePeriodCreatesPositive(): void
    {
        $period = Period::of(years: -1, months: -2, days: -3);
        $result = $period->negated();

        self::assertSame(1, $result->years);
        self::assertSame(2, $result->months);
        self::assertSame(3, $result->days);
    }

    public function testNormalizedConvertsExcessMonthsToYears(): void
    {
        $period = Period::of(years: 1, months: 15, days: 3);
        $result = $period->normalized();

        self::assertSame(2, $result->years);
        self::assertSame(3, $result->months);
        self::assertSame(3, $result->days);
    }

    public function testNormalizedHandlesNegativeMonths(): void
    {
        $period = Period::of(years: 2, months: -6, days: 10);
        $result = $period->normalized();

        self::assertSame(1, $result->years);
        self::assertSame(6, $result->months);
        self::assertSame(10, $result->days);
    }

    public function testNormalizedReturnsSameInstanceWhenAlreadyNormalized(): void
    {
        $period = Period::of(years: 1, months: 6, days: 3);
        $result = $period->normalized();

        self::assertSame($period, $result);
    }

    public function testNormalizedWithLargeMonthValues(): void
    {
        $period = Period::of(months: 50);
        $result = $period->normalized();

        self::assertSame(4, $result->years);
        self::assertSame(2, $result->months);
    }

    public function testToTotalMonthsCalculatesTotalMonths(): void
    {
        $period = Period::of(years: 2, months: 3);

        self::assertSame(27, $period->toTotalMonths());
    }

    public function testToTotalMonthsIgnoresDays(): void
    {
        $period = Period::of(years: 1, months: 6, days: 100);

        self::assertSame(18, $period->toTotalMonths());
    }

    public function testEqualsReturnsTrueForEqualPeriods(): void
    {
        $period1 = Period::of(years: 1, months: 2, days: 3);
        $period2 = Period::of(years: 1, months: 2, days: 3);

        self::assertTrue($period1->equals($period2));
    }

    public function testEqualsReturnsFalseForDifferentPeriods(): void
    {
        $period1 = Period::of(years: 1, months: 2, days: 3);
        $period2 = Period::of(years: 1, months: 2, days: 4);

        self::assertFalse($period1->equals($period2));
    }

    public function testToStringFormatsZeroPeriod(): void
    {
        $period = Period::zero();

        self::assertSame('P0D', (string) $period);
    }

    public function testToStringFormatsFullPeriod(): void
    {
        $period = Period::of(years: 2, months: 3, days: 4);

        self::assertSame('P2Y3M4D', (string) $period);
    }

    public function testToStringFormatsOnlyYears(): void
    {
        $period = Period::ofYears(5);

        self::assertSame('P5Y', (string) $period);
    }

    public function testToStringFormatsOnlyMonths(): void
    {
        $period = Period::ofMonths(6);

        self::assertSame('P6M', (string) $period);
    }

    public function testToStringFormatsOnlyDays(): void
    {
        $period = Period::ofDays(15);

        self::assertSame('P15D', (string) $period);
    }

    public function testToStringFormatsNegativeValues(): void
    {
        $period = Period::of(years: -1, months: -2, days: -3);

        self::assertSame('P-1Y-2M-3D', (string) $period);
    }

    public function testParsesPeriodWithAllComponents(): void
    {
        $period = Period::parse('P2Y3M4D');

        self::assertSame(2, $period->years);
        self::assertSame(3, $period->months);
        self::assertSame(4, $period->days);
    }

    public function testParsesZeroPeriod(): void
    {
        $period = Period::parse('P0D');

        self::assertTrue($period->isZero());
    }

    public function testParsesOnlyYears(): void
    {
        $period = Period::parse('P5Y');

        self::assertSame(5, $period->years);
        self::assertSame(0, $period->months);
        self::assertSame(0, $period->days);
    }

    public function testParsesOnlyMonths(): void
    {
        $period = Period::parse('P6M');

        self::assertSame(0, $period->years);
        self::assertSame(6, $period->months);
        self::assertSame(0, $period->days);
    }

    public function testParsesOnlyDays(): void
    {
        $period = Period::parse('P15D');

        self::assertSame(0, $period->years);
        self::assertSame(0, $period->months);
        self::assertSame(15, $period->days);
    }

    public function testParsesNegativeValues(): void
    {
        $period = Period::parse('P-1Y-2M-3D');

        self::assertSame(-1, $period->years);
        self::assertSame(-2, $period->months);
        self::assertSame(-3, $period->days);
    }

    public function testParseThrowsExceptionForEmptyString(): void
    {
        $this->expectException(InvalidDuration::class);
        $this->expectExceptionMessage('Invalid ISO 8601 duration string: ');

        Period::parse('');
    }

    public function testParseThrowsExceptionWhenNotStartingWithP(): void
    {
        $this->expectException(InvalidDuration::class);
        $this->expectExceptionMessage('Invalid ISO 8601 duration string: 1Y2M3D');

        Period::parse('1Y2M3D');
    }

    public function testParseThrowsExceptionForJustP(): void
    {
        $this->expectException(InvalidDuration::class);
        $this->expectExceptionMessage('Duration string cannot be just \'P\'.');

        Period::parse('P');
    }

    public function testParseThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(InvalidDuration::class);
        $this->expectExceptionMessage('Invalid ISO 8601 duration string: P1X');

        Period::parse('P1X');
    }

    public function testParseRoundTripPreservesValue(): void
    {
        $original = Period::of(years: 1, months: 6, days: 15);
        $parsed = Period::parse((string) $original);

        self::assertTrue($original->equals($parsed));
    }

    public function testSerializeAndUnserializePreservesValue(): void
    {
        $original = Period::of(years: 2, months: 3, days: 4);
        $serialized = \serialize($original);
        $unserialized = \unserialize($serialized);

        self::assertInstanceOf(Period::class, $unserialized);
        self::assertTrue($original->equals($unserialized));
    }

    public function testUnserializeThrowsExceptionForMalformedData(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Serialized Brzuchal\DateTime\Period payload is malformed.');

        $period = new Period();
        $period->__unserialize([]);
    }

    public function testUnserializeThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $period = new Period();
        $period->__unserialize(['value' => 123]);
    }
}
