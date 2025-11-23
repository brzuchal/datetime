<?php declare(strict_types=1);

namespace Tests;

use Brzuchal\DateTime\ZoneId;
use Brzuchal\DateTime\ZoneOffset;
use Brzuchal\DateTime\InvalidTimezone;
use PHPUnit\Framework\TestCase;

class ZoneIdTest extends TestCase
{
    public function testOfCreatesZoneId(): void
    {
        $zone = ZoneId::of('Europe/Warsaw');

        self::assertSame('Europe/Warsaw', $zone->id);
        self::assertSame('Europe/Warsaw', (string) $zone);
    }

    public function testOfValidatesZoneExists(): void
    {
        $this->expectException(InvalidTimezone::class);
        $this->expectExceptionMessage('Unknown timezone');

        ZoneId::of('Invalid/Timezone');
    }

    public function testUTC(): void
    {
        $utc1 = ZoneId::UTC();
        $utc2 = ZoneId::UTC();

        self::assertSame('UTC', $utc1->id);
        self::assertSame($utc1, $utc2, 'UTC should be cached');
    }

    public function testSystemDefault(): void
    {
        $system1 = ZoneId::systemDefault();
        $system2 = ZoneId::systemDefault();

        self::assertNotEmpty($system1->id);
        self::assertSame($system1, $system2, 'System default should be cached');
    }

    public function testGetRules(): void
    {
        $zone = ZoneId::of('Europe/Warsaw');
        $rules = $zone->getRules();

        self::assertNotNull($rules);
        self::assertSame('Europe/Warsaw', $rules->zoneId);
    }

    public function testGetOffsetForTimestamp(): void
    {
        $zone = ZoneId::of('Europe/Warsaw');

        // Winter time (CET = UTC+1)
        $winterTimestamp = \strtotime('2024-01-15 12:00:00 UTC');
        $winterOffset = $zone->getOffsetForTimestamp($winterTimestamp);

        self::assertSame(3600, $winterOffset, 'Warsaw winter should be UTC+1');

        // Summer time (CEST = UTC+2)
        $summerTimestamp = \strtotime('2024-07-15 12:00:00 UTC');
        $summerOffset = $zone->getOffsetForTimestamp($summerTimestamp);

        self::assertSame(7200, $summerOffset, 'Warsaw summer should be UTC+2');
    }

    public function testGetZoneOffsetForTimestamp(): void
    {
        $zone = ZoneId::of('America/New_York');
        $timestamp = \strtotime('2024-01-15 12:00:00 UTC');

        $offset = $zone->getZoneOffsetForTimestamp($timestamp);

        self::assertInstanceOf(ZoneOffset::class, $offset);
        self::assertSame(-18000, $offset->totalSeconds, 'NYC winter should be UTC-5');
    }

    public function testEquals(): void
    {
        $zone1 = ZoneId::of('Europe/Warsaw');
        $zone2 = ZoneId::of('Europe/Warsaw');
        $zone3 = ZoneId::of('America/New_York');

        self::assertTrue($zone1->equals($zone2));
        self::assertFalse($zone1->equals($zone3));
    }

    public function testToString(): void
    {
        $zone = ZoneId::of('Asia/Tokyo');

        self::assertSame('Asia/Tokyo', (string) $zone);
    }
}
