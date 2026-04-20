<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Timezone;

/**
 * Represents a single transition in the time zone:
 *   - timestamp: when the transition occurs (as string, can be 64-bit)
 *   - typeIndex: which TzifTypeInfo index becomes active at that time
 *
 * @internal This class is not part of the public API.
 */
final readonly class TzifTransition
{
    public function __construct(
        public string $timestamp,
        public int $typeIndex,
    ) {
    }
}
