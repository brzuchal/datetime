<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

/**
 * Holds the final parsed data of a TZif file (versions 1, 2, 3, or 4).
 *   - version: '1', '2', '3', '4', or "1" if the header byte was '\0'
 *   - isV2Plus: true if the file contains a second (64-bit) data block
 *   - types: an array of TzifTypeInfo
 *   - transitions: an array of TzifTransition
 *   - abbreviations: an array of raw abbreviation data (usually just one string)
 *   - leapSecondData: optional leap second info
 *   - posixString: optional POSIX-style TZ string from the file trailer
 *
 * @internal This class is not part of the public API.
 */
final readonly class TzifFile
{
    /**
     * @param TzifTypeInfo[]                       $types
     * @param TzifTransition[]                     $transitions
     * @param string[]                             $abbreviations
     * @param array<int,array{timestamp:string,corr:int}> $leapSecondData
     */
    public function __construct(
        public string $version,
        public bool $isV2Plus,
        public array $types,
        public array $transitions,
        public array $abbreviations,
        public array $leapSecondData,
        public string|null $posixString,
    ) {}
}
