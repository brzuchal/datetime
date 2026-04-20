<?php

declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Timezone\ZoneRules;
use Brzuchal\DateTime\Timezone\ZoneRulesProvider;

/**
 * A time-zone ID, such as `Europe/Paris` or `+02:00`.
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
    ) {
    }

    /**
     * Get ZoneId for an IANA timezone identifier.
     *
     * @throws InvalidZoneId If timezone is not found.
     */
    public static function of(string $zoneId): self
    {
        // It might be an offset-based ZoneId
        if (Offset::isValidOffsetString($zoneId)) {
            return self::ofOffset(Offset::parse($zoneId));
        }

        // Validate that zone exists by attempting to load rules
        ZoneRulesProvider::getRules($zoneId);

        return new self($zoneId);
    }

    /**
     * Obtains an instance of ZoneId wrapping an offset.
     */
    public static function ofOffset(Offset $offset): self
    {
        return new self($offset->toString());
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
     * Resets the cached system default timezone.
     *
     * This is useful if the system timezone is changed via date_default_timezone_set()
     * during the execution of the script.
     */
    public static function resetSystemDefault(): void
    {
        self::$systemDefault = null;
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
        $instant = Instant::ofEpochSecond($utcTimestamp);

        return $this->getRules()->getOffset($instant)->totalSeconds;
    }

    /**
     * Get UtcOffset for a given UTC timestamp.
     */
    public function getZoneOffsetForTimestamp(int $utcTimestamp): Offset
    {
        $offsetSeconds = $this->getOffsetForTimestamp($utcTimestamp);

        return Offset::ofTotalSeconds($offsetSeconds);
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
