<?php declare(strict_types=1);

namespace Tests\Timezone;

use Brzuchal\DateTime\Timezone\ZoneRulesProvider;
use Brzuchal\DateTime\InvalidTimezone;
use PHPUnit\Framework\TestCase;

class ZoneRulesProviderTest extends TestCase
{
    public function testGetRulesForEuropeWarsaw(): void
    {
        $rules = ZoneRulesProvider::getRules('Europe/Warsaw');

        self::assertNotNull($rules);
        self::assertSame('Europe/Warsaw', $rules->zoneId);
        self::assertNotEmpty($rules->tzifPath);
        self::assertFileExists($rules->tzifPath);
    }

    public function testGetRulesForAmericaNewYork(): void
    {
        $rules = ZoneRulesProvider::getRules('America/New_York');

        self::assertNotNull($rules);
        self::assertSame('America/New_York', $rules->zoneId);
    }

    public function testGetRulesForUTC(): void
    {
        $rules = ZoneRulesProvider::getRules('UTC');

        self::assertNotNull($rules);
        self::assertSame('UTC', $rules->zoneId);
    }

    public function testCachesRules(): void
    {
        ZoneRulesProvider::clearCache();

        $rules1 = ZoneRulesProvider::getRules('Europe/Warsaw');
        $rules2 = ZoneRulesProvider::getRules('Europe/Warsaw');

        self::assertSame($rules1, $rules2, 'Should return same instance from cache');
    }

    public function testThrowsOnUnknownZone(): void
    {
        $this->expectException(InvalidTimezone::class);
        $this->expectExceptionMessage('Unknown timezone');

        ZoneRulesProvider::getRules('Invalid/Timezone');
    }

    public function testClearCache(): void
    {
        $rules1 = ZoneRulesProvider::getRules('Europe/Warsaw');

        ZoneRulesProvider::clearCache();

        $rules2 = ZoneRulesProvider::getRules('Europe/Warsaw');

        self::assertNotSame($rules1, $rules2, 'Should create new instance after cache clear');
    }

    public function testGetAvailableZoneIds(): void
    {
        $zoneIds = ZoneRulesProvider::getAvailableZoneIds();

        self::assertIsArray($zoneIds);
        self::assertNotEmpty($zoneIds, 'Should find system timezones');
        self::assertContains('Europe/Warsaw', $zoneIds);
        self::assertContains('America/New_York', $zoneIds);
        self::assertContains('UTC', $zoneIds);
    }

    public function testSetCustomTzDataPath(): void
    {
        // Use test fixtures path
        $customPath = __DIR__ . '/../fixtures/zoneinfo';

        ZoneRulesProvider::setCustomTzDataPath($customPath);

        $rules = ZoneRulesProvider::getRules('Europe/Warsaw');

        self::assertStringContainsString('fixtures/zoneinfo', $rules->tzifPath);

        // Reset to default (system)
        ZoneRulesProvider::setCustomTzDataPath('/usr/share/zoneinfo');
    }
}
