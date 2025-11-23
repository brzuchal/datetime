<?php declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\OffsetDateTime;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\ZoneOffset;
use PHPUnit\Framework\TestCase;

class OffsetDateTimeTest extends TestCase
{
    public function testOfCreatesOffsetDateTime(): void
    {
        $odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneOffset::of(2, 0));

        self::assertSame(2024, $odt->dateTime->year);
        self::assertSame(3, $odt->dateTime->month);
        self::assertSame(15, $odt->dateTime->day);
        self::assertSame(14, $odt->dateTime->hour);
        self::assertSame(30, $odt->dateTime->minute);
        self::assertSame(7200, $odt->offset->totalSeconds);
    }

    public function testOfDefaultsToUTC(): void
    {
        $odt = OffsetDateTime::of(2024, 3, 15, 14, 30);

        self::assertSame(0, $odt->offset->totalSeconds);
    }

    public function testOfDateTimeAndOffset(): void
    {
        $dt = LocalDateTime::of(2024, 7, 20, 9, 15);
        $offset = ZoneOffset::of(-5, 0);

        $odt = OffsetDateTime::ofDateTimeAndOffset($dt, $offset);

        self::assertSame($dt, $odt->dateTime);
        self::assertSame($offset, $odt->offset);
    }

    public function testNow(): void
    {
        $odt = OffsetDateTime::now(ZoneOffset::UTC());

        self::assertInstanceOf(OffsetDateTime::class, $odt);
        self::assertSame(0, $odt->offset->totalSeconds);
    }

    public function testNowWithSystemOffset(): void
    {
        $odt = OffsetDateTime::now();

        self::assertInstanceOf(OffsetDateTime::class, $odt);
        // System offset will vary, just check it exists
        self::assertIsInt($odt->offset->totalSeconds);
    }

    public function testToInstant(): void
    {
        // 2024-03-15T14:00:00+02:00 = 2024-03-15T12:00:00Z
        $odt = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
        $instant = $odt->toInstant();

        // Verify by converting back to UTC
        self::assertSame(2024, (int) \gmdate('Y', $instant->epochSecond));
        self::assertSame(3, (int) \gmdate('n', $instant->epochSecond));
        self::assertSame(15, (int) \gmdate('j', $instant->epochSecond));
        self::assertSame(12, (int) \gmdate('G', $instant->epochSecond)); // 14:00+02:00 = 12:00 UTC
    }

    public function testWithOffsetSameInstant(): void
    {
        // 2024-03-15T14:00:00+02:00
        $odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));

        // Change to +05:00 keeping same instant
        $odt2 = $odt1->withOffsetSameInstant(ZoneOffset::of(5, 0));

        // Local time should be 17:00 (14:00 + 3 hours difference)
        self::assertSame(17, $odt2->dateTime->hour);
        self::assertSame(5 * 3600, $odt2->offset->totalSeconds);

        // Same instant
        self::assertSame($odt1->toInstant()->epochSecond, $odt2->toInstant()->epochSecond);
    }

    public function testWithOffsetSameInstantReturnsSelfIfSameOffset(): void
    {
        $odt = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
        $result = $odt->withOffsetSameInstant(ZoneOffset::of(2, 0));

        self::assertSame($odt, $result);
    }

    public function testWithOffsetSameLocal(): void
    {
        // 2024-03-15T14:00:00+02:00
        $odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));

        // Change to +05:00 keeping same local time
        $odt2 = $odt1->withOffsetSameLocal(ZoneOffset::of(5, 0));

        // Local time should stay 14:00
        self::assertSame(14, $odt2->dateTime->hour);
        self::assertSame(5 * 3600, $odt2->offset->totalSeconds);

        // Different instants (3 hours apart)
        self::assertSame(3 * 3600, $odt1->toInstant()->epochSecond - $odt2->toInstant()->epochSecond);
    }

    public function testWithOffsetSameLocalReturnsSelfIfSameOffset(): void
    {
        $odt = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
        $result = $odt->withOffsetSameLocal(ZoneOffset::of(2, 0));

        self::assertSame($odt, $result);
    }

    public function testToString(): void
    {
        $odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 45, 0, ZoneOffset::of(2, 30));

        self::assertSame('2024-03-15T14:30:45+02:30', (string) $odt);
    }

    public function testEquals(): void
    {
        // Same instant, different offsets
        $odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
        $odt2 = OffsetDateTime::of(2024, 3, 15, 12, 0, 0, 0, ZoneOffset::UTC());

        self::assertTrue($odt1->equals($odt2), 'Same instant should be equal');

        $odt3 = OffsetDateTime::of(2024, 3, 15, 15, 0, 0, 0, ZoneOffset::of(2, 0));
        self::assertFalse($odt1->equals($odt3), 'Different instants should not be equal');
    }

    public function testIsBefore(): void
    {
        $odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
        $odt2 = OffsetDateTime::of(2024, 3, 15, 15, 0, 0, 0, ZoneOffset::of(2, 0));

        self::assertTrue($odt1->isBefore($odt2));
        self::assertFalse($odt2->isBefore($odt1));
    }

    public function testIsAfter(): void
    {
        $odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneOffset::of(2, 0));
        $odt2 = OffsetDateTime::of(2024, 3, 15, 15, 0, 0, 0, ZoneOffset::of(2, 0));

        self::assertFalse($odt1->isAfter($odt2));
        self::assertTrue($odt2->isAfter($odt1));
    }
}
