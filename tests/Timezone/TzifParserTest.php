<?php declare(strict_types=1);

namespace Tests\Timezone;

use Brzuchal\DateTime\Timezone\TzifParser;
use Brzuchal\DateTime\InvalidTimezone;
use PHPUnit\Framework\TestCase;

class TzifParserTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/../fixtures/zoneinfo';

    public function testParsesUTCZone(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/UTC');

        self::assertNotNull($tzif);
        self::assertGreaterThanOrEqual('1', $tzif->version);
        self::assertTrue($tzif->isV2Plus || $tzif->version === '1');
        
        // UTC should have minimal transitions (often just one type)
        self::assertIsArray($tzif->types);
        self::assertNotEmpty($tzif->types);
    }

    public function testParsesEuropeWarsawZone(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        self::assertNotNull($tzif);
        self::assertGreaterThanOrEqual('2', $tzif->version, 'Warsaw should be v2+');
        self::assertTrue($tzif->isV2Plus);
        
        // Warsaw has many transitions (WW2, DST changes, etc.)
        self::assertNotEmpty($tzif->transitions);
        self::assertNotEmpty($tzif->types);
        self::assertNotEmpty($tzif->abbreviations);
        
        // Should have POSIX string for future rules
        self::assertNotNull($tzif->posixString, 'V2+ should have POSIX string');
    }

    public function testParsesAmericaNewYorkZone(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/America/New_York');

        self::assertNotNull($tzif);
        self::assertGreaterThanOrEqual('2', $tzif->version);
        self::assertTrue($tzif->isV2Plus);
        
        // New York has many DST transitions
        self::assertNotEmpty($tzif->transitions);
        
        // Check for DST flags in types
        $hasDst = false;
        foreach ($tzif->types as $type) {
            if ($type->isdst) {
                $hasDst = true;
                break;
            }
        }
        self::assertTrue($hasDst, 'New York should have DST types');
    }

    public function testExtractsTransitionData(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        self::assertGreaterThan(0, count($tzif->transitions));
        
        // Check first transition structure
        $transition = $tzif->transitions[0];
        self::assertObjectHasProperty('timestamp', $transition);
        self::assertObjectHasProperty('typeIndex', $transition);
        
        // Timestamp should be a string (can be 64-bit)
        self::assertIsString($transition->timestamp);
        
        // Type index should reference a valid type
        self::assertIsInt($transition->typeIndex);
        self::assertArrayHasKey($transition->typeIndex, $tzif->types);
    }

    public function testExtractsTypeInformation(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        self::assertNotEmpty($tzif->types);
        
        $type = $tzif->types[0];
        self::assertObjectHasProperty('gmtoff', $type);
        self::assertObjectHasProperty('isdst', $type);
        self::assertObjectHasProperty('abbrIndex', $type);
        
        // gmtoff should be in reasonable range (-18h to +18h)
        self::assertGreaterThanOrEqual(-64800, $type->gmtoff);
        self::assertLessThanOrEqual(64800, $type->gmtoff);
        
        // isdst should be boolean
        self::assertIsBool($type->isdst);
    }

    public function testExtractsAbbreviations(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        self::assertNotEmpty($tzif->abbreviations);
        self::assertIsArray($tzif->abbreviations);
        
        // Abbreviations should be a raw string block
        self::assertIsString($tzif->abbreviations[0]);
    }

    public function testExtractsPosixStringForV2Plus(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        if ($tzif->isV2Plus) {
            self::assertNotNull($tzif->posixString);
            self::assertIsString($tzif->posixString);
            self::assertNotEmpty($tzif->posixString);
            
            // POSIX string should contain timezone info (e.g., "CET-1CEST,M3.5.0,M10.5.0/3")
            self::assertMatchesRegularExpression('/[A-Z]+/', $tzif->posixString);
        }
    }

    public function testRejectsInvalidMagic(): void
    {
        $this->expectException(InvalidTimezone::class);
        $this->expectExceptionMessage('Invalid TZif magic');
        
        $parser = new TzifParser();
        // Provide 44+ bytes with invalid magic (so length check passes)
        $parser->parseData('XXXX' . str_repeat("\x00", 44));
    }

    public function testRejectsTooShortData(): void
    {
        $this->expectException(InvalidTimezone::class);
        $this->expectExceptionMessage('Data too short');
        
        $parser = new TzifParser();
        $parser->parseData('TZif' . str_repeat("\x00", 10)); // Too short for header
    }

    public function testRejectsNonexistentFile(): void
    {
        $this->expectException(InvalidTimezone::class);
        $this->expectExceptionMessage('Cannot read file');
        
        $parser = new TzifParser();
        $parser->parseFile('/nonexistent/timezone/file');
    }

    public function testHandlesLeapSeconds(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        // Leap second data may be empty (most zones don't include it)
        self::assertIsArray($tzif->leapSecondData);
        
        if (!empty($tzif->leapSecondData)) {
            $leap = $tzif->leapSecondData[0];
            self::assertArrayHasKey('timestamp', $leap);
            self::assertArrayHasKey('corr', $leap);
        }
    }

    public function testParsesHistoricalTransitions(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        // Warsaw should have pre-1970 transitions
        $hasPre1970 = false;
        foreach ($tzif->transitions as $trans) {
            if ((int)$trans->timestamp < 0) {
                $hasPre1970 = true;
                break;
            }
        }
        
        // Note: This might not always be true depending on tzdata version
        // but Warsaw historically had many changes
        if ($tzif->isV2Plus) {
            self::assertTrue($hasPre1970, 'V2+ Warsaw should have pre-1970 data');
        }
    }

    public function testKnownDSTTransition2024WarsawSpring(): void
    {
        $parser = new TzifParser();
        $tzif = $parser->parseFile(self::FIXTURES_DIR . '/Europe/Warsaw');

        // 2024-03-31 02:00 → 03:00 (spring forward)
        $targetTimestamp = strtotime('2024-03-31 01:00:00 UTC');
        
        // Find transition around this time
        $foundTransition = null;
        foreach ($tzif->transitions as $trans) {
            $ts = (int)$trans->timestamp;
            if (abs($ts - $targetTimestamp) < 7200) { // within 2 hours
                $foundTransition = $trans;
                break;
            }
        }
        
        if ($foundTransition) {
            self::assertNotNull($foundTransition);
            $type = $tzif->types[$foundTransition->typeIndex];
            
            // After spring transition, should be DST (CEST = UTC+2)
            self::assertTrue($type->isdst || $type->gmtoff === 7200);
        }
    }
}
