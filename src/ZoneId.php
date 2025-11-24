<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Timezone\ZoneRules;
use Brzuchal\DateTime\Timezone\ZoneRulesProvider;

/**
 * IANA timezone identifier (e.g., "Europe/Warsaw", "America/New_York").
 *
 * Immutable wrapper that lazily loads {@see ZoneRules} from {@see ZoneRulesProvider}.
 */
final class ZoneId implements \Stringable
{
    /** @var self|null Cached UTC instance */
    private static self|null $utc = null;

    /** @var self|null Cached system default instance */
    private static self|null $systemDefault = null;

    private function __construct(
        public readonly string $id,
    ) {}

    /**
     * Get ZoneId for an IANA timezone identifier.
     *
     * @throws InvalidTimezone If timezone is not found.
     */
    public static function of(string $zoneId): self
    {
        // Validate that zone exists by attempting to load rules
        ZoneRulesProvider::getRules($zoneId);

        return new self($zoneId);
    }

    /**
     * Get ZoneId for UTC.
     */
    public static function UTC(): self
    {
        return self::$utc ??= new self('UTC');
    }

    /**
     * Get system default timezone.
     *
     * Uses PHP's date_default_timezone_get().
     */
    public static function systemDefault(): self
    {
        if (self::$systemDefault === null) {
            $systemId = \date_default_timezone_get();
            self::$systemDefault = new self($systemId);
        }

        return self::$systemDefault;
    }

    /**
     * Get the timezone rules for this zone.
     *
     * Lazy-loaded from {@see ZoneRulesProvider}.
     */
    public function getRules(): ZoneRules
    {
        return ZoneRulesProvider::getRules($this->id);
    }

    /**
     * Get the offset in seconds for a given UTC timestamp.
     */
    public function getOffsetForTimestamp(int $utcTimestamp): int
    {
        return $this->getRules()->getOffsetForTimestamp($utcTimestamp);
    }

    /**
     * Get ZoneOffset for a given UTC timestamp.
     */
    public function getZoneOffsetForTimestamp(int $utcTimestamp): ZoneOffset
    {
        $offsetSeconds = $this->getOffsetForTimestamp($utcTimestamp);

        return ZoneOffset::ofTotalSeconds($offsetSeconds);
    }

    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * Check if this zone equals another.
     */
    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
