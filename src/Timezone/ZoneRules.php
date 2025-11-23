<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

use Brzuchal\DateTime\LocalDateTime;

/**
 * Timezone rules with lazy tzif file parsing and POSIX future transitions.
 * 
 * Hybrid approach:
 * - Historical transitions: loaded from tzif file on demand
 * - Future transitions: computed from POSIX TZ string
 * 
 * @internal This class is not part of the public API.
 */
final class ZoneRules
{
    /**
     * @var array<int, array{0:int, 1:int, 2:bool, 3:string}>|null
     */
    private array|null $parsedTransitions = null;

    public function __construct(
        public readonly string $zoneId,
        public readonly int $cutoffTimestamp,
        public readonly string $posixRule,
        public readonly string $tzifPath,
    ) {
    }

    /**
     * Get the offset in seconds for a given UTC timestamp.
     */
    public function getOffsetForTimestamp(int $utcTimestamp): int
    {
        if ($utcTimestamp >= $this->cutoffTimestamp && $this->posixRule !== '') {
            return $this->computePosixOffset($utcTimestamp);
        }

        $this->parsedTransitions ??= $this->parseTzifFile($this->tzifPath);

        return $this->findOffsetInParsedTransitions($utcTimestamp);
    }

    /**
     * Parse tzif file and extract transitions.
     * 
     * @return array<int, array{0:int, 1:int, 2:bool, 3:string}>
     */
    private function parseTzifFile(string $path): array
    {
        $parser = new TzifParser();
        $tf = $parser->parseFile($path);
        $abbrevData = $tf->abbreviations[0] ?? '';

        $result = [];
        foreach ($tf->transitions as $transitionObj) {
            $ts = (int) $transitionObj->timestamp;
            $typeInfo = $tf->types[$transitionObj->typeIndex];
            $offset = $typeInfo->gmtoff;
            $isDst = $typeInfo->isdst;
            $abbr = $this->extractAbbreviation($abbrevData, $typeInfo->abbrIndex);

            $result[] = [
                $ts,     // [0] UTC timestamp
                $offset, // [1] offset in seconds
                $isDst,  // [2] bool
                $abbr,   // [3] abbreviation
            ];
        }

        \usort($result, fn($a, $b) => $a[0] <=> $b[0]);

        return $result;
    }

    private function extractAbbreviation(string $raw, int $offset): string
    {
        if ($offset < 0 || $offset >= \strlen($raw)) {
            return '';
        }

        $res = '';
        for ($pos = $offset; $pos < \strlen($raw); $pos++) {
            if ($raw[$pos] === "\0") {
                break;
            }
            $res .= $raw[$pos];
        }

        return $res;
    }

    private function findOffsetInParsedTransitions(int $utcTimestamp): int
    {
        if ($this->parsedTransitions === null || \count($this->parsedTransitions) === 0) {
            return 0;
        }

        $found = $this->parsedTransitions[0][1];
        foreach ($this->parsedTransitions as $tr) {
            if ($tr[0] <= $utcTimestamp) {
                $found = $tr[1];
            } else {
                break;
            }
        }

        return $found;
    }

    /**
     * Compute offset from POSIX TZ string for future timestamps.
     * 
     * POSIX format: "STD offset DST,start,end"
     * Example: "CET-1CEST,M3.5.0,M10.5.0/3"
     */
    private function computePosixOffset(int $utcTimestamp): int
    {
        // Extract year from UTC timestamp
        $year = (int) \gmdate('Y', $utcTimestamp);
        
        $parts = \explode(',', $this->posixRule);

        if (\count($parts) < 3) {
            return 0; // Invalid POSIX rule
        }

        [$prefix, $startSpec, $endSpec] = $parts;
        $parsed = $this->parsePrefix($prefix);
        $baseOffsetSec = $parsed['baseOffsetSec'];
        $dstOffsetSec = $parsed['dstOffsetSec'];

        $dstStartUtc = $this->calcTransitionUtc($year, $startSpec, $baseOffsetSec, $dstOffsetSec, false);
        $dstEndUtc = $this->calcTransitionUtc($year, $endSpec, $baseOffsetSec, $dstOffsetSec, true);

        if ($dstStartUtc < $dstEndUtc) {
            // Northern hemisphere
            if ($utcTimestamp >= $dstStartUtc && $utcTimestamp < $dstEndUtc) {
                return $dstOffsetSec;
            }

            return $baseOffsetSec;
        }

        // Southern hemisphere
        if ($utcTimestamp < $dstStartUtc && $utcTimestamp >= $dstEndUtc) {
            return $baseOffsetSec;
        }

        return $dstOffsetSec;
    }

    /**
     * Parse POSIX TZ prefix to extract base and DST offsets.
     * 
     * @return array{baseOffsetSec:int, dstOffsetSec:int}
     */
    private function parsePrefix(string $prefix): array
    {
        $offsetPos = \strcspn($prefix, '+-0123456789');
        if ($offsetPos === \strlen($prefix)) {
            return ['baseOffsetSec' => 0, 'dstOffsetSec' => 3600];
        }

        $rest = \substr($prefix, $offsetPos);
        if (!\preg_match('/^([+-]?\d+)/', $rest, $m)) {
            return ['baseOffsetSec' => 0, 'dstOffsetSec' => 3600];
        }

        $stdOffsetNum = (int) $m[1];
        $baseSec = -$stdOffsetNum * 3600;  // POSIX offsets are inverted
        $dstSec = $baseSec + 3600;

        return ['baseOffsetSec' => $baseSec, 'dstOffsetSec' => $dstSec];
    }

    /**
     * Calculate UTC timestamp of a DST transition for a given year.
     */
    private function calcTransitionUtc(
        int $year,
        string $spec,
        int $baseOffsetSec,
        int $dstOffsetSec,
        bool $isEnd,
    ): int {
        $hour = 2;
        if (\str_contains($spec, '/')) {
            [$spec, $h] = \explode('/', $spec);
            $hour = (int) $h;
        }

        $parts = \explode('.', $spec);
        if (\count($parts) < 3) {
            // Fallback to March 1st
            return $this->localToUtcTimestamp($year, 3, 1, $hour, $isEnd ? $dstOffsetSec : $baseOffsetSec);
        }

        $month = (int) \substr($parts[0], 1);  // Skip 'M' prefix
        $weekSpec = $parts[1];
        $dayOfWeek = (int) $parts[2];

        if ($weekSpec === '5') {
            // Last occurrence of dayOfWeek in month
            $day = $this->lastDayOfWeekInMonth($year, $month, $dayOfWeek);

            return $this->localToUtcTimestamp($year, $month, $day, $hour, $isEnd ? $dstOffsetSec : $baseOffsetSec);
        }

        // First occurrence (simplified - just use day 1)
        return $this->localToUtcTimestamp($year, $month, 1, $hour, $isEnd ? $dstOffsetSec : $baseOffsetSec);
    }

    private function localToUtcTimestamp(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $offsetSec,
    ): int {
        // Use mktime to create local timestamp, then subtract offset
        $localTs = \mktime($hour, 0, 0, $month, $day, $year);
        
        if ($localTs === false) {
            return 0; // Fallback for invalid date
        }

        return $localTs - $offsetSec;
    }

    private function lastDayOfWeekInMonth(int $year, int $month, int $wantedDow): int
    {
        $daysInMonth = $this->daysInMonth($year, $month);

        // Start from last day and work backwards
        for ($day = $daysInMonth; $day >= 1; $day--) {
            $ts = \mktime(0, 0, 0, $month, $day, $year);
            
            if ($ts === false) {
                continue; // Skip invalid dates
            }
            
            $dow = (int) \date('N', $ts);  // 1 (Monday) to 7 (Sunday)

            if ($dow === $wantedDow) {
                return $day;
            }
        }

        return $daysInMonth; // Fallback
    }

    private function daysInMonth(int $year, int $month): int
    {
        /** @var array<int, int> */
        static $mlen = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        
        if ($month < 1 || $month > 12) {
            return 31; // Fallback
        }
        
        $days = $mlen[$month - 1];

        if ($month === 2 && $this->isLeapYear($year)) {
            $days = 29;
        }

        return $days;
    }

    private function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }
}
