<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

/**
 * Represents a single TTInfo entry in the TZif file:
 *   - gmtOff: offset from UTC in seconds
 *   - isDst: whether DST is in effect
 *   - abbrIndex: index of the abbreviation in the abbreviations data
 *   - isStd: standard indicator (from the 'ttisstd' array if present)
 *   - isUt:  UT indicator (from the 'ttisut' array if present)
 *
 * @internal This class is not part of the public API.
 */
final readonly class TzifTypeInfo
{
    public function __construct(
        public int $gmtOff,
        public bool $isDst,
        public int $abbrIndex,
        public bool $isStd,
        public bool $isUt,
    ) {}
}
