<?php declare(strict_types=1);

namespace Tests\Temporal;

use Brzuchal\DateTime\Format\TemporalFields;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\Temporal\TemporalQueries;
use PHPUnit\Framework\TestCase;

final class TemporalQueriesTest extends TestCase
{
    public function testLocalDateQueryReturnsDate(): void
    {
        $fields = new TemporalFields(year: 2024, month: 7, day: 21);

        $result = $fields->query(TemporalQueries::localDate());

        self::assertInstanceOf(LocalDate::class, $result);
        self::assertSame('2024-07-21', (string) $result);
    }

    public function testLocalDateQueryReturnsNullWhenIncomplete(): void
    {
        $fields = new TemporalFields(year: 2024, day: 5);

        self::assertNull($fields->query(TemporalQueries::localDate()));
    }

    public function testLocalTimeQueryReturnsTime(): void
    {
        $fields = new TemporalFields(hour: 9, minute: 15, second: 30, nano: 123_000_000);

        $result = $fields->query(TemporalQueries::localTime());

        self::assertInstanceOf(LocalTime::class, $result);
        self::assertSame('09:15:30.123', (string) $result);
    }

    public function testLocalDateTimeQueryReturnsDateTime(): void
    {
        $fields = new TemporalFields(
            year: 2023,
            month: 12,
            day: 31,
            hour: 23,
            minute: 45,
            second: 0,
        );

        $result = $fields->query(TemporalQueries::localDateTime());

        self::assertInstanceOf(LocalDateTime::class, $result);
        self::assertSame('2023-12-31T23:45:00', (string) $result);
    }
}
