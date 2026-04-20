<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

use Brzuchal\DateTime\InvalidZoneRules;

/**
 * Parser for TZif binary timezone files (RFC 9636).
 *
 * @internal This class is not part of the public API.
 */
final class TzifParser
{
    private const string TZ_MAGIC = 'TZif';
    private const int HEADER_LEN = 44; // V1 header size in bytes

    /**
     * Reads a TZif file from the disk and parses it.
     *
     * @throws InvalidZoneRules
     */
    public function parseFile(string $filename): TzifFile
    {
        $data = @\file_get_contents($filename);
        if ($data === false) {
            throw new InvalidZoneRules(\sprintf('Unable to read TZIF file: %s', $filename));
        }

        return $this->parseData($data);
    }

    /**
     * Parses the raw binary data from a TZif file (any version).
     *
     * @throws InvalidZoneRules
     */
    public function parseData(string $data): TzifFile
    {
        // At least 44 bytes for the first header
        if (\strlen($data) < self::HEADER_LEN) {
            throw new InvalidZoneRules('Data too short for TZif header.');
        }

        // 1) Parse the first (V1) header
        $header1 = $this->parseHeaderV1(\substr($data, 0, self::HEADER_LEN));
        $version = $header1['version']; // might be '\0', '2', '3', '4'
        $v1TimeCnt = $header1['timecnt'];
        $v1TypeCnt = $header1['typecnt'];
        $v1CharCnt = $header1['charcnt'];
        $v1LeapCnt = $header1['leapcnt'];
        $v1TtisstdCnt = $header1['ttisstdcnt'];
        $v1TtisutCnt = $header1['ttisutcnt'];

        $isV2Plus = ($version !== "\0");

        // offset right after the first header
        $offset = self::HEADER_LEN;

        // 2) Parse the 32-bit data block (V1 section)
        $blockV1 = $this->parseSection(
            data: $data,
            startOffset: $offset,
            timeCnt: $v1TimeCnt,
            typeCnt: $v1TypeCnt,
            charCnt: $v1CharCnt,
            leapCnt: $v1LeapCnt,
            ttisstdCnt: $v1TtisstdCnt,
            ttisutCnt: $v1TtisutCnt,
            is32bit: true,
        );
        $offset = $blockV1['newOffset'];

        // store these as the "final" if V2 is not present
        $finalTypes = $blockV1['types'];
        $finalTransitions = $blockV1['transitions'];
        $finalAbbrevs = $blockV1['abbrevs'];
        $finalLeapData = $blockV1['leaps'];
        $posixString = null;

        // 3) If a version is not '\0', parse the second (V2, 64-bit) section
        if ($isV2Plus) {
            // read the second header
            if (\strlen($data) < $offset + self::HEADER_LEN) {
                throw new InvalidZoneRules(\sprintf('Unsupported TZIF version: %s', $version));
            }

            $header2 = $this->parseHeaderV1(\substr($data, $offset, self::HEADER_LEN));
            $offset += self::HEADER_LEN;

            $v2TimeCnt = $header2['timecnt'];
            $v2TypeCnt = $header2['typecnt'];
            $v2CharCnt = $header2['charcnt'];
            $v2LeapCnt = $header2['leapcnt'];
            $v2TtisstdCnt = $header2['ttisstdcnt'];
            $v2TtisutCnt = $header2['ttisutcnt'];

            // parse the 64-bit section
            $blockV2 = $this->parseSection(
                data: $data,
                startOffset: $offset,
                timeCnt: $v2TimeCnt,
                typeCnt: $v2TypeCnt,
                charCnt: $v2CharCnt,
                leapCnt: $v2LeapCnt,
                ttisstdCnt: $v2TtisstdCnt,
                ttisutCnt: $v2TtisutCnt,
                is32bit: false,
            );
            $offset = $blockV2['newOffset'];

            // now the final data is from V2
            $finalTypes = $blockV2['types'];
            $finalTransitions = $blockV2['transitions'];
            $finalAbbrevs = $blockV2['abbrevs'];
            $finalLeapData = $blockV2['leaps'];
        }

        // 4) Possibly parse a trailing POSIX string if V2+.
        if ($isV2Plus && $offset < \strlen($data)) {
            // According to RFC 8536, after the second data block, we expect
            // a newline, then a POSIX-TZ string, then another newline.
            $posixPart = \substr($data, $offset);
            if (\strlen($posixPart) > 0 && $posixPart[0] === "\n") {
                $posixPart = \substr($posixPart, 1);
                $lnPos = \strpos($posixPart, "\n");
                if ($lnPos !== false) {
                    $posixString = \substr($posixPart, 0, $lnPos);
                }
            }
        }

        // 5) Return the TzifFile object
        return new TzifFile(
            version: ($version === "\0" ? '1' : $version),
            isV2Plus: $isV2Plus,
            types: $finalTypes,
            transitions: $finalTransitions,
            abbreviations: $finalAbbrevs,
            leapSecondData: $finalLeapData,
            posixString: $posixString,
        );
    }

    /**
     * Parses a 44-byte header (for both V1 and V2+).
     *
     * @return array{version:string,ttisutcnt:int,ttisstdcnt:int,leapcnt:int,timecnt:int,typecnt:int,typecnt:int,charcnt:int}
     * @throws InvalidZoneRules
     */
    private function parseHeaderV1(string $rawHeader): array
    {
        $magic = \substr($rawHeader, 0, 4);
        if ($magic !== self::TZ_MAGIC) {
            throw new InvalidZoneRules(\sprintf('Invalid TZif magic: %s', $magic));
        }

        // version byte at [4]
        $versionByte = $rawHeader[4];

        // parse the next 6 fields (4 bytes each, big-endian)
        /** @var array{ttisutcnt:int,ttisstdcnt:int,leapcnt:int,timecnt:int,typecnt:int,charcnt:int}|false $counts */
        $counts = \unpack('Nttisutcnt/Nttisstdcnt/Nleapcnt/Ntimecnt/Ntypecnt/Ncharcnt', \substr($rawHeader, 20, 24));
        \assert($counts !== false);

        return [
            'version' => $versionByte,
            'ttisutcnt' => $counts['ttisutcnt'],
            'ttisstdcnt' => $counts['ttisstdcnt'],
            'leapcnt' => $counts['leapcnt'],
            'timecnt' => $counts['timecnt'],
            'typecnt' => $counts['typecnt'],
            'charcnt' => $counts['charcnt'],
        ];
    }

    /**
     * Parse the main section of transitions, TTInfo, abbreviations, leaps, etc.
     *
     * @return array{types:array<int,TzifTypeInfo>,transitions:array<int,TzifTransition>,abbrevs:array<int,string>,leaps:array<int,array{timestamp:string,corr:int}>,newOffset:int}
     */
    private function parseSection(
        string $data,
        int $startOffset,
        int $timeCnt,
        int $typeCnt,
        int $charCnt,
        int $leapCnt,
        int $ttisstdCnt,
        int $ttisutCnt,
        bool $is32bit,
    ): array {
        $offset = $startOffset;

        // 1) Transition timestamps
        $transTimestamps = [];
        if ($timeCnt > 0) {
            $size = $is32bit ? 4 : 8;
            $rawTimes = \substr($data, $offset, $timeCnt * $size);
            $offset += $timeCnt * $size;
            $transTimestamps = $this->parseTransitionTimestamps($rawTimes, $timeCnt, $is32bit);
        }

        // 2) Transition type indices
        $transitionTypes = [];
        if ($timeCnt > 0) {
            $rawTypes = \substr($data, $offset, $timeCnt);
            $offset += $timeCnt;
            /** @var array<int,int> $unpacked */
            $unpacked = \unpack(\sprintf('C%s', $timeCnt), $rawTypes);
            $transitionTypes = \array_values($unpacked);
        }

        // 3) TTInfo: each is 6 bytes
        $types = [];
        for ($i = 0; $i < $typeCnt; $i++) {
            $raw6 = \substr($data, $offset, 6);
            $offset += 6;
            /** @var array{gmtoff:int,isdst:int,abbrind:int}|false $tmp */
            $tmp = \unpack('Ngmtoff/Cisdst/Cabbrind', $raw6);
            \assert($tmp !== false);

            // convert gmtoff to signed 32-bit (handle two's complement)
            $gmtoff = $tmp['gmtoff'];
            if ($gmtoff & 0x80000000) {
                $gmtoff -= 0x100000000;
            }

            $isdst = ($tmp['isdst'] !== 0);
            $abbrIndex = $tmp['abbrind'];

            // placeholders for isStd and isUt (filled later if arrays exist)
            $types[] = new TzifTypeInfo(
                gmtOff: $gmtoff,
                isDst: $isdst,
                abbrIndex: $abbrIndex,
                isStd: false,
                isUt: false,
            );
        }

        // 4) Abbreviations block (charCnt bytes)
        $abbrevsRaw = \substr($data, $offset, $charCnt);
        $offset += $charCnt;
        $abbrevStrings = $this->extractAbbrevs($abbrevsRaw);

        // 5) Leap second data
        $leapData = [];
        if ($leapCnt > 0) {
            $sizeTime = $is32bit ? 4 : 8;
            $recordSize = $sizeTime + 4; // time + correction
            $rawLeap = \substr($data, $offset, $leapCnt * $recordSize);
            $offset += $leapCnt * $recordSize;
            $leapData = $this->parseLeapSeconds($rawLeap, $leapCnt, $is32bit);
        }

        // 6) ttisstd array (ttisstdCnt bytes)
        if ($ttisstdCnt > 0) {
            $rawStd = \substr($data, $offset, $ttisstdCnt);
            $offset += $ttisstdCnt;
            /** @var array<int,int> $unpacked */
            $unpacked = \unpack(\sprintf('C%s', $ttisstdCnt), $rawStd);
            $stdArray = \array_values($unpacked);
            // fill in "isStd" flags
            for ($i = 0; $i < $typeCnt && $i < $ttisstdCnt; $i++) {
                $t = $types[$i];
                $types[$i] = new TzifTypeInfo(
                    gmtOff: $t->gmtOff,
                    isDst: $t->isDst,
                    abbrIndex: $t->abbrIndex,
                    isStd: ($stdArray[$i] !== 0),
                    isUt: $t->isUt,
                );
            }
        }

        // 7) ttisut array (ttisutCnt bytes)
        if ($ttisutCnt > 0) {
            $rawUt = \substr($data, $offset, $ttisutCnt);
            $offset += $ttisutCnt;
            /** @var array<int,int> $unpacked */
            $unpacked = \unpack(\sprintf('C%s', $ttisutCnt), $rawUt);
            $utArray = \array_values($unpacked);
            for ($i = 0; $i < $typeCnt && $i < $ttisutCnt; $i++) {
                $t = $types[$i];
                $types[$i] = new TzifTypeInfo(
                    gmtOff: $t->gmtOff,
                    isDst: $t->isDst,
                    abbrIndex: $t->abbrIndex,
                    isStd: $t->isStd,
                    isUt: ($utArray[$i] !== 0),
                );
            }
        }

        // 8) Build TzifTransition objects
        $transitionObjs = [];
        for ($i = 0; $i < $timeCnt; $i++) {
            $transitionObjs[] = new TzifTransition(
                timestamp: $transTimestamps[$i],
                typeIndex: $transitionTypes[$i],
            );
        }

        return [
            'types' => $types,
            'transitions' => $transitionObjs,
            'abbrevs' => $abbrevStrings,
            'leaps' => $leapData,
            'newOffset' => $offset,
        ];
    }

    /**
     * Reads transition timestamps from a raw string (32-bit or 64-bit big-endian).
     *
     * @return array<int,string>
     */
    private function parseTransitionTimestamps(string $raw, int $count, bool $is32bit): array
    {
        $result = [];
        if ($count <= 0) {
            return $result;
        }

        if ($is32bit) {
            // 32-bit approach (convert unsigned to signed int)
            /** @var array<int,int>|false $arr */
            $arr = \unpack(\sprintf('N%s', $count), $raw);
            \assert($arr !== false);
            foreach ($arr as $val) {
                // Convert to signed 32-bit (two's complement)
                if (($val & 0x80000000) !== 0) {
                    $val = (int) ($val - 4294967296);
                }

                $result[] = (string) $val; // store as string
            }
        } else {
            // 64-bit approach with GMP
            $size = 8;
            for ($i = 0; $i < $count; $i++) {
                $chunk = \substr($raw, $i * $size, $size);
                /** @var array{hi:int,lo:int}|false $hiLo */
                $hiLo = \unpack('Nhi/Nlo', $chunk);
                \assert($hiLo !== false);

                // Extract 32-bit parts
                $hi = $hiLo['hi'] & 0xffffffff;
                $lo = $hiLo['lo'] & 0xffffffff;

                // Combine hi and lo as a 64-bit big integer using GMP
                $val64 = \gmp_init((string) $hi, 10);
                $val64 = \gmp_mul($val64, \gmp_init('4294967296', 10)); // * 2^32
                $val64 = \gmp_add($val64, \gmp_init((string) $lo, 10));

                // If sign bit set => convert to signed (two's complement)
                if (($hiLo['hi'] & 0x80000000) !== 0) {
                    $val64 = \gmp_sub($val64, \gmp_init('18446744073709551616', 10));
                }

                // Convert to string
                $valStr = \gmp_strval($val64);

                $result[] = $valStr;
            }
        }

        return $result;
    }

    /**
     * Parses leap second records, each containing:
     *   - timestamp (32-bit or 64-bit)
     *   - correction (32-bit)
     *
     * @return array<int,array{timestamp:string,corr:int}>
     */
    private function parseLeapSeconds(string $raw, int $leapCnt, bool $is32bit): array
    {
        $res = [];
        $szTime = $is32bit ? 4 : 8;
        $recordSize = $szTime + 4; // time + corr

        for ($i = 0; $i < $leapCnt; $i++) {
            $chunk = \substr($raw, $i * $recordSize, $recordSize);
            $timePart = \substr($chunk, 0, $szTime);
            $corrPart = \substr($chunk, $szTime, 4);

            // parse time
            $arrTime = $this->parseTransitionTimestamps($timePart, 1, $is32bit);
            $timestamp = $arrTime[0];

            // parse correction (32-bit big-endian)
            /** @var array{corr:int}|false $tmp */
            $tmp = \unpack('Ncorr', $corrPart);
            \assert($tmp !== false);
            $corr = $tmp['corr'];
            // Convert to signed 32-bit (two's complement)
            if (($corr & 0x80000000) !== 0) {
                $corr = (int) ($corr - 4294967296);
            }

            $res[] = [
                'timestamp' => $timestamp,
                'corr' => $corr,
            ];
        }

        return $res;
    }

    /**
     * Returns an array of strings. In this simplified approach, we only
     * store one raw string. The abbreviation index points into this string.
     *
     * @return array<int,string>
     */
    private function extractAbbrevs(string $raw): array
    {
        // For memory optimization, keep the raw block unsplit.
        // The TzifTypeInfo.abbrIndex is used to find the zero-terminated substring on demand.
        return [$raw];
    }
}
