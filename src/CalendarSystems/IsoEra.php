<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

/**
 * ISO-8601 era classification.
 */
enum IsoEra: string
{
    case BeforeCommonEra = 'BCE';
    case CommonEra = 'CE';

    public static function fromYear(int $year): self
    {
        return $year <= 0 ? self::BeforeCommonEra : self::CommonEra;
    }

    public function shortCode(): string
    {
        return $this->value;
    }

    public function name(): string
    {
        return match ($this) {
            self::BeforeCommonEra => 'Before Common Era',
            self::CommonEra => 'Common Era',
        };
    }

    public function ordinal(): int
    {
        return match ($this) {
            self::BeforeCommonEra => 0,
            self::CommonEra => 1,
        };
    }
}
