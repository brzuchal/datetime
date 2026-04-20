<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\InvalidZoneRules;
use Brzuchal\DateTime\Offset;

/**
 * Timezone rules with lazy tzif file parsing and POSIX future transitions.
 *
 * Hybrid approach:
 * - Historical transitions: loaded from a Tzif file on demand
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
    public function getOffset(Instant $instant): Offset
    {
        $utcTimestamp = $instant->getEpochSecond();

        if ($utcTimestamp >= $this->cutoffTimestamp && $this->posixRule !== '') {
            return Offset::ofTotalSeconds($this->computePosixOffset($utcTimestamp));
        }

        $this->parsedTransitions ??= $this->parseTzifFile($this->tzifPath);

        return Offset::ofTotalSeconds($this->findOffsetInParsedTransitions($utcTimestamp));
    }

    /**
     * Get the offset in seconds for a given UTC timestamp (compatibility method).
     */
    public function getOffsetForTimestamp(int $utcTimestamp): int
    {
        $instant = \Brzuchal\DateTime\Instant::ofEpochSecond($utcTimestamp);

        return $this->getOffset($instant)->totalSeconds;
    }

    /**
     * Parse tzif file and extract transitions.
     *
     * @return array<int, array{0:int, 1:int, 2:bool, 3:string}>
     * @throws InvalidZoneRules
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
            $offset = $typeInfo->gmtOff;
            $isDst = $typeInfo->isDst;
            $abbr = $this->extractAbbreviation($abbrevData, $typeInfo->abbrIndex);

            $result[] = [
                $ts,     // [0] UTC timestamp
                $offset, // [1] offset in seconds
                $isDst,  // [2] bool
                $abbr,   // [3] abbreviation
            ];
        }

        \usort($result, static fn ($a, $b) => $a[0] <=> $b[0]);

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
            if ($tr[0] > $utcTimestamp) {
                break;
            }

            $found = $tr[1];
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
        $epochDay = \intdiv($utcTimestamp, 86400);
        if ($utcTimestamp < 0 && $utcTimestamp % 86400 !== 0) {
            $epochDay--;
        }

        $dateParts = \Brzuchal\DateTime\Internal\IsoCalendar::dateFromEpochDay($epochDay);
        $year = $dateParts[0];

        $parts = \explode(',', $this->posixRule);
        $prefix = $parts[0];
        $parsed = $this->parsePrefix($prefix);
        $baseOffsetSec = $parsed['baseOffsetSec'];

        if (\count($parts) < 3) {
            // No DST rules specified, return standard offset
            return $baseOffsetSec;
        }

        [$prefix, $startSpec, $endSpec] = $parts;
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

        $week = (int) $weekSpec;

        if ($week === 5) {
            // Last occurrence of dayOfWeek in month
            $day = $this->lastDayOfWeekInMonth($year, $month, $dayOfWeek);
        } else {
            // Nth occurrence (1 = first, 2 = second, 3 = third, 4 = fourth)
            $day = $this->nthDayOfWeekInMonth($year, $month, $week, $dayOfWeek);
        }

        return $this->localToUtcTimestamp($year, $month, $day, $hour, $isEnd ? $dstOffsetSec : $baseOffsetSec);
    }

    private function localToUtcTimestamp(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $offsetSec,
    ): int {
        $epochDay = \Brzuchal\DateTime\Internal\IsoCalendar::epochDayFromDate($year, $month, $day);
        // Hour only, minute/second 0
        $secondOfDay = $hour * 3600;

        $localTs = $epochDay * 86400 + $secondOfDay;

        return $localTs - $offsetSec;
    }

    /**
     * Find the day-of-month for the Nth occurrence of the given POSIX day-of-week
     * (0 = Sunday … 6 = Saturday) within the given month.
     *
     * If $nth exceeds the number of occurrences in the month (e.g., asking for the
     * 5th Monday when there are only 4), the last occurrence is returned — matching
     * POSIX semantics.
     */
    private function nthDayOfWeekInMonth(int $year, int $month, int $nth, int $wantedDow): int
    {
        $daysInMonth = $this->daysInMonth($year, $month);
        $count = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $epochDay = \Brzuchal\DateTime\Internal\IsoCalendar::epochDayFromDate($year, $month, $day);

            // ISO DOW: 1970-01-01 was Thursday → offset=3 → 0=Monday, 6=Sunday
            $dayOfWeekIndex = ($epochDay + 3) % 7;
            if ($dayOfWeekIndex < 0) {
                $dayOfWeekIndex += 7;
            }

            // Convert ISO DOW (0=Mon…6=Sun) to POSIX DOW (0=Sun…6=Sat)
            $posixDow = ($dayOfWeekIndex + 1) % 7;

            if ($posixDow !== $wantedDow) {
                continue;
            }

            $count++;
            if ($count === $nth) {
                return $day;
            }
        }

        // Fallback: return the last found occurrence (handles edge-case where
        // $nth > actual occurrences — POSIX says treat as last occurrence)
        return $this->lastDayOfWeekInMonth($year, $month, $wantedDow);
    }

    private function lastDayOfWeekInMonth(int $year, int $month, int $wantedDow): int
    {
        $daysInMonth = $this->daysInMonth($year, $month);

        // Start from last day and work backwards
        for ($day = $daysInMonth; $day >= 1; $day--) {
            $epochDay = \Brzuchal\DateTime\Internal\IsoCalendar::epochDayFromDate($year, $month, $day);

            // 1970-01-01 was a Thursday => offset=3 => 0 => Monday, 6 => Sunday
            $dayOfWeekIndex = ($epochDay + 3) % 7;
            if ($dayOfWeekIndex < 0) {
                $dayOfWeekIndex += 7;
            }

            // LocalDate uses 0 (Monday) to 6 (Sunday)
            // POSIX TZ uses 0 (Sunday) to 6 (Saturday)
            // Convert LocalDate dow to POSIX dow
            $posixDow = ($dayOfWeekIndex + 1) % 7;

            if ($posixDow === $wantedDow) {
                return $day;
            }
        }

        return $daysInMonth; // Fallback
    }

    private function daysInMonth(int $year, int $month): int
    {
        /** @var array<int, int> $mlen */
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
