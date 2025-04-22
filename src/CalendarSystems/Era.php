<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

final readonly class Era
{
    public function __construct(
        public int $ordinal,
        public string $code,
        public string $label,
        public int|null $startEpochDay = null,
        public int|null $endEpochDay = null,
    ) {}

    public function equals(self $other): bool
    {
        return $this->ordinal === $other->ordinal && $this->code === $other->code;
    }

    public function isBefore(self $other): bool
    {
        return $this->ordinal < $other->ordinal;
    }

    public function isAfter(self $other): bool
    {
        return $this->ordinal > $other->ordinal;
    }

    public function includesEpochDay(int $epochDay): bool
    {
        return ($this->startEpochDay === null || $epochDay >= $this->startEpochDay) &&
            ($this->endEpochDay === null || $epochDay <= $this->endEpochDay);
    }
}
