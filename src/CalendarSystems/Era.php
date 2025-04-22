<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

/**
 * Represents a distinct period or segment in time identified by an ordinal, code, and label.
 * Optionally, the era can have a defined range of epoch days.
 */
final readonly class Era
{
    public function __construct(
        public int $ordinal,
        public string $code,
        public string $label,
        public int|null $startEpochDay = null,
        public int|null $endEpochDay = null,
    ) {}

    /**
     * Compares this object with another instance of the same type to determine equality.
     *
     * @param self $other The object to compare with the current instance.
     * @return bool True if the objects are considered equal, otherwise false.
     */
    public function equals(self $other): bool
    {
        return $this->ordinal === $other->ordinal && $this->code === $other->code;
    }

    /**
     * Determines if this object is considered to occur before another instance of the same type.
     *
     * @param self $other The object to compare with the current instance.
     * @return bool True if this object occurs before the given object, otherwise false.
     */
    public function isBefore(self $other): bool
    {
        return $this->ordinal < $other->ordinal;
    }

    /**
     * Compares the current instance with another to determine if it occurs later.
     *
     * @param self $other The instance to compare against.
     * @return bool Returns true if the current instance occurs after the given instance, otherwise false.
     */
    public function isAfter(self $other): bool
    {
        return $this->ordinal > $other->ordinal;
    }

    /**
     * Determines whether the specified epoch day falls within the range defined
     * by the startEpochDay and endEpochDay properties.
     *
     * @param int $epochDay The epoch day to check.
     * @return bool True if the epoch day is within bounds, false otherwise.
     */
    public function includesEpochDay(int $epochDay): bool
    {
        return ($this->startEpochDay === null || $epochDay >= $this->startEpochDay) &&
            ($this->endEpochDay === null || $epochDay <= $this->endEpochDay);
    }
}
