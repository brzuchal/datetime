<?php

declare(strict_types=1);

namespace Tests\Format;

use Brzuchal\DateTime\Format\DateTimeFormat;
use Brzuchal\DateTime\Format\DateTimeFormatter;
use Brzuchal\DateTime\Format\InvalidPattern;
use Brzuchal\DateTime\Format\UnsupportedPatternSymbol;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;
use PHPUnit\Framework\TestCase;

final class DateTimeFormatterTest extends TestCase
{
    public function testOfUsesEnumPattern(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoLocalDate);
        $date = LocalDate::of(2024, 3, 5);

        self::assertSame('2024-03-05', $formatter->format($date));
    }

    public function testFromPatternSupportsCustomPattern(): void
    {
        $formatter = DateTimeFormatter::fromPattern('Ymd');
        $date = LocalDate::of(2024, 3, 5);

        self::assertSame('20240305', $formatter->format($date));
    }

    public function testOfIsoLocalDateTimeFormat(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoLocalDateTime);
        $dateTime = LocalDateTime::of(2024, 7, 14, 9, 30, 15);

        self::assertSame('2024-07-14T09:30:15', $formatter->format($dateTime));
    }

    public function testOfIsoBasicLocalTimeFormat(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoBasicLocalTime);
        $time = LocalTime::of(9, 30, 15);

        self::assertSame('093015', $formatter->format($time));
    }

    public function testOfIsoLocalTimeNanoFormat(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoLocalTimeNano);
        $time = LocalTime::of(9, 30, 15, 123_456_789);

        self::assertSame('09:30:15.123456789', $formatter->format($time));
    }

    public function testIsoLocalTimeNanoFormatOmitsFractionWhenZero(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoLocalTimeNano);
        $time = LocalTime::of(9, 30, 15);

        self::assertSame('09:30:15', $formatter->format($time));
    }

    public function testIsoBasicLocalDateTimeParsing(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoBasicLocalDateTime);
        $fields = $formatter->parse('20240714T093015');

        self::assertSame(2024, $fields->year);
        self::assertSame(7, $fields->month);
        self::assertSame(14, $fields->day);
        self::assertSame(9, $fields->hour);
        self::assertSame(30, $fields->minute);
        self::assertSame(15, $fields->second);
    }

    public function testIsoBasicLocalDateTimeParsingWithFraction(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoBasicLocalDateTime);
        $fields = $formatter->parse('20240714T093015.123456789');

        self::assertSame(2024, $fields->year);
        self::assertSame(7, $fields->month);
        self::assertSame(14, $fields->day);
        self::assertSame(9, $fields->hour);
        self::assertSame(30, $fields->minute);
        self::assertSame(15, $fields->second);
        self::assertSame(123_456_789, $fields->nano);
    }

    public function testFromPatternWithEscapedLiteral(): void
    {
        $formatter = DateTimeFormatter::fromPattern('Y-m-d\\TH:i:s');
        $dateTime = LocalDateTime::of(2024, 7, 14, 9, 30, 15);

        self::assertSame('2024-07-14T09:30:15', $formatter->format($dateTime));
    }

    public function testFromPatternWithNanoPlaceholderParsesAndFormats(): void
    {
        $formatter = DateTimeFormatter::fromPattern('H:i:s[fff]');
        $time = LocalTime::of(1, 2, 3, 45_000_000);

        self::assertSame('01:02:03.045', $formatter->format($time));

        $fields = $formatter->parse('01:02:03.045');

        self::assertSame(45_000_000, $fields->nano);
    }

    public function testFromPatternRejectsEmptyInput(): void
    {
        $this->expectException(InvalidPattern::class);

        DateTimeFormatter::fromPattern(' , ');
    }

    public function testFromPatternRejectsUnsupportedSymbols(): void
    {
        $this->expectException(UnsupportedPatternSymbol::class);

        DateTimeFormatter::fromPattern('Y-m-d T');
    }

    public function testDayMonthWithoutLeadingZeros(): void
    {
        $formatter = DateTimeFormatter::fromPattern('Y-n-j');
        $date = LocalDate::of(2024, 11, 7);

        self::assertSame('2024-11-7', $formatter->format($date));
    }

    public function testTextualMonthAndOrdinalSuffix(): void
    {
        $formatter = DateTimeFormatter::fromPattern('D, jS M Y');
        $date = LocalDate::of(2024, 3, 1);

        self::assertSame('Fri, 1st Mar 2024', $formatter->format($date));
    }

    public function testMicrosecondsAndMilliseconds(): void
    {
        $formatter = DateTimeFormatter::fromPattern('H:i:s.u v');
        $time = LocalTime::of(12, 15, 5, 123_456_000);

        self::assertSame('12:15:05.123456 123', $formatter->format($time));
    }

    public function testIsoWeekAndWeekYearFormatting(): void
    {
        $formatter = DateTimeFormatter::fromPattern('o-\WW-N');
        $date = LocalDate::of(2020, 12, 31); // ISO week 53 of 2020

        self::assertSame('2020-W53-4', $formatter->format($date));
    }

    public function testIsoLocalDateTimeEmitsFractionWhenPresent(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoLocalDateTime);
        $dateTime = LocalDateTime::of(2024, 7, 14, 9, 30, 15, 987_654_321);

        self::assertSame('2024-07-14T09:30:15.987654321', $formatter->format($dateTime));
    }

    public function testIsoLocalDateTimeSecondsFormat(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoLocalDateTimeSeconds);
        $dateTime = LocalDateTime::of(2024, 7, 14, 9, 30, 15, 987_654_321);

        self::assertSame('2024-07-14T09:30:15', $formatter->format($dateTime));
    }

    public function testIsoWeekDateFormat(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoWeekDate);
        $date = LocalDate::of(2020, 12, 31);

        self::assertSame('2020-W53-4', $formatter->format($date));
    }

    public function testIsoOrdinalDateFormat(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::IsoOrdinalDate);
        $date = LocalDate::of(2024, 1, 24);

        self::assertSame('2024-23', $formatter->format($date));
    }

    public function testSqlDateTimeFormat(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::SqlDateTime);
        $dateTime = LocalDateTime::of(2024, 1, 24, 15, 30, 45);

        self::assertSame('2024-01-24 15:30:45', $formatter->format($dateTime));
    }

    public function testSqlDateTimeNanoFormatOmitsFractionWhenZero(): void
    {
        $formatter = DateTimeFormatter::of(DateTimeFormat::SqlDateTimeNano);
        $dateTime = LocalDateTime::of(2024, 1, 24, 15, 30, 45);

        self::assertSame('2024-01-24 15:30:45', $formatter->format($dateTime));

        $withFraction = LocalDateTime::of(2024, 1, 24, 15, 30, 45, 123_000_000);

        self::assertSame('2024-01-24 15:30:45.123', $formatter->format($withFraction));
    }
}
