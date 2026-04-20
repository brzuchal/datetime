<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

enum TemporalField
{
    case Era;

    case Year;
    case Month;
    case Day;

    case Hour;     // 24-hour
    case Hour12;   // 12-hour
    case Minute;
    case Second;
    case Nano;
    case AmPm;

    case DayOfWeek;
    case DayOfYear;

    case WeekOfMonth;
    case WeekOfYear;
    case WeekBasedYear;

    public function symbol(): string
    {
        return match ($this) {
            self::Year          => 'Y',
            self::Month         => 'm',
            self::Day           => 'd',
            self::Hour          => 'H',
            self::Hour12        => 'h',
            self::Minute        => 'i',
            self::Second        => 's',
            self::Nano          => 'f',
            self::AmPm          => 'a',
            self::WeekBasedYear => 'o',
            default            => '',
        };
    }

    public static function fromSymbol(string $symbol): self|null
    {
        return match ($symbol) {
            'Y', 'y', 'X' => self::Year,
            'o'           => self::WeekBasedYear,
            'm', 'n'      => self::Month,
            'd', 'j'      => self::Day,
            'H', 'G'      => self::Hour,
            'h', 'g'      => self::Hour12,
            'i'           => self::Minute,
            's'           => self::Second,
            'f', 'u', 'v' => self::Nano,
            'a', 'A'      => self::AmPm,
            'w', 'N'      => self::DayOfWeek,
            'z'           => self::DayOfYear,
            'W'           => self::WeekOfYear,
            default       => null,
        };
    }
}
