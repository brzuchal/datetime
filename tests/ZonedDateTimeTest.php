<?php

declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\Offset;
use Brzuchal\DateTime\ZonedDateTime;
use Brzuchal\DateTime\ZoneId;
use PHPUnit\Framework\TestCase;

class ZonedDateTimeTest extends TestCase
{
    public function testOfCreatesZonedDateTime(): void
    {
        $zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));

        self::assertSame(2024, $zdt->dateTime->year);
        self::assertSame(3, $zdt->dateTime->month);
        self::assertSame(15, $zdt->dateTime->day);
        self::assertSame(14, $zdt->dateTime->hour);
        self::assertSame(30, $zdt->dateTime->minute);
        self::assertSame('Europe/Warsaw', $zdt->zone->id);
    }

    public function testOfDefaultsToUTC(): void
    {
        $zdt = ZonedDateTime::of(2024, 3, 15, 14, 30);

        self::assertSame('UTC', $zdt->zone->id);
    }

    public function testOfLocal(): void
    {
        $dt = LocalDateTime::of(2024, 7, 20, 9, 15);
        $zone = ZoneId::of('America/New_York');

        $zdt = ZonedDateTime::ofLocal($dt, $zone);

        self::assertSame($dt, $zdt->dateTime);
        self::assertSame($zone->id, $zdt->zone->id);
    }

    public function testOfInstant(): void
    {
        $instant = Instant::of(1710504000); // 2024-03-15 12:00:00 UTC
        $zone = ZoneId::of('Europe/Warsaw');

        $zdt = ZonedDateTime::ofInstant($instant, $zone);

        // Warsaw is UTC+1 in winter, so 12:00 UTC = 13:00 local
        self::assertSame(2024, $zdt->dateTime->year);
        self::assertSame(3, $zdt->dateTime->month);
        self::assertSame(15, $zdt->dateTime->day);
        self::assertSame(13, $zdt->dateTime->hour); // UTC+1
    }

    public function testNow(): void
    {
        $zdt = ZonedDateTime::now(ZoneId::UTC());

        self::assertInstanceOf(ZonedDateTime::class, $zdt);
        self::assertSame('UTC', $zdt->zone->id);
    }

    public function testNowWithSystemZone(): void
    {
        $zdt = ZonedDateTime::now();

        self::assertInstanceOf(ZonedDateTime::class, $zdt);
        // System zone will vary
        self::assertNotEmpty($zdt->zone->id);
    }

    public function testToInstant(): void
    {
        // 2024-03-15T14:00:00+01:00[Europe/Warsaw] = 2024-03-15T13:00:00Z
        $zdt = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        $instant = $zdt->toInstant();

        // Verify UTC time (Warsaw is usually UTC+1 in winter)
        self::assertSame(2024, (int) \gmdate('Y', $instant->epochSecond));
        self::assertSame(3, (int) \gmdate('n', $instant->epochSecond));
        self::assertSame(15, (int) \gmdate('j', $instant->epochSecond));
        self::assertSame(13, (int) \gmdate('G', $instant->epochSecond)); // 14:00+01:00 = 13:00 UTC
    }

    public function testToOffsetDateTime(): void
    {
        $zdt = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        $odt = $zdt->toOffsetDateTime();

        self::assertSame($zdt->dateTime, $odt->dateTime);
        self::assertSame($zdt->offset->totalSeconds, $odt->offset->totalSeconds);
    }

    public function testWithZoneSameInstant(): void
    {
        // 2024-03-15T14:00:00+01:00[Europe/Warsaw]
        $warsaw = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));

        // Change to Tokyo (UTC+9)
        $tokyo = $warsaw->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));

        // Local time should be 22:00 (14:00 + 8 hours difference)
        self::assertSame(22, $tokyo->dateTime->hour);
        self::assertSame('Asia/Tokyo', $tokyo->zone->id);

        // Same instant
        self::assertSame($warsaw->toInstant()->epochSecond, $tokyo->toInstant()->epochSecond);
    }

    public function testWithZoneSameInstantReturnsSelfIfSameZone(): void
    {
        $zdt = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        $result = $zdt->withZoneSameInstant(ZoneId::of('Europe/Warsaw'));

        self::assertSame($zdt, $result);
    }

    public function testWithZoneSameLocal(): void
    {
        // 2024-03-15T14:00:00+01:00[Europe/Warsaw]
        $warsaw = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));

        // Change to Tokyo keeping same local time
        $tokyo = $warsaw->withZoneSameLocal(ZoneId::of('Asia/Tokyo'));

        // Local time should stay 14:00
        self::assertSame(14, $tokyo->dateTime->hour);
        self::assertSame('Asia/Tokyo', $tokyo->zone->id);

        // Different instants
        self::assertNotSame($warsaw->toInstant()->epochSecond, $tokyo->toInstant()->epochSecond);
    }

    public function testWithZoneSameLocalReturnsSelfIfSameZone(): void
    {
        $zdt = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        $result = $zdt->withZoneSameLocal(ZoneId::of('Europe/Warsaw'));

        self::assertSame($zdt, $result);
    }

    public function testToString(): void
    {
        $zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 45, 0, ZoneId::of('Europe/Warsaw'));

        // ISO-8601 extended with zone
        self::assertStringContainsString('2024-03-15T14:30:45', (string) $zdt);
        self::assertStringContainsString('[Europe/Warsaw]', (string) $zdt);
    }

    public function testEquals(): void
    {
        // Same instant, different zones
        $warsaw = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        $tokyo = $warsaw->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));

        self::assertTrue($warsaw->equals($tokyo), 'Same instant should be equal');

        $later = ZonedDateTime::of(2024, 3, 15, 15, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        self::assertFalse($warsaw->equals($later), 'Different instants should not be equal');
    }

    public function testIsBefore(): void
    {
        $earlier = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        $later = ZonedDateTime::of(2024, 3, 15, 15, 0, 0, 0, ZoneId::of('Europe/Warsaw'));

        self::assertTrue($earlier->isBefore($later));
        self::assertFalse($later->isBefore($earlier));
    }

    public function testIsAfter(): void
    {
        $earlier = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
        $later = ZonedDateTime::of(2024, 3, 15, 15, 0, 0, 0, ZoneId::of('Europe/Warsaw'));

        self::assertFalse($earlier->isAfter($later));
        self::assertTrue($later->isAfter($earlier));
    }

    public function testDSTTransitionSpringForward(): void
    {
        // 2024-03-31 02:00:00 CEST spring forward (gap)
        // In Europe/Warsaw, 02:00 → 03:00 (02:30 doesn't exist)
        $beforeDST = ZonedDateTime::of(2024, 3, 31, 1, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
        $afterDST = ZonedDateTime::of(2024, 3, 31, 3, 30, 0, 0, ZoneId::of('Europe/Warsaw'));

        // Before DST: UTC+1
        self::assertSame(3600, $beforeDST->offset->totalSeconds);

        // After DST: UTC+2
        self::assertSame(7200, $afterDST->offset->totalSeconds);
    }

    public function testDSTTransitionFallBack(): void
    {
        // 2024-10-27 03:00:00 CET fall back (overlap)
        // In Europe/Warsaw, 03:00 → 02:00 (02:30 exists twice)
        $beforeDST = ZonedDateTime::of(2024, 10, 27, 1, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
        $afterDST = ZonedDateTime::of(2024, 10, 27, 3, 30, 0, 0, ZoneId::of('Europe/Warsaw'));

        // Before fall-back: UTC+2 (CEST)
        self::assertSame(7200, $beforeDST->offset->totalSeconds);

        // After fall-back: UTC+1 (CET)
        self::assertSame(3600, $afterDST->offset->totalSeconds);
    }
}
