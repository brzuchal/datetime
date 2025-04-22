<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

final class CalendarSystemRegistry
{
    /** @var array<string, CalendarSystem> */
    private static array $registry;

    public static function register(CalendarSystem $calendar): void
    {
        self::$registry ??= self::default();

        self::$registry[$calendar->name()] = $calendar;
    }

    public static function get(string $name): CalendarSystem
    {
        self::$registry ??= self::default();

        return self::$registry[$name]
            ?? throw new \InvalidArgumentException("Unknown calendar system: $name");
    }

    public static function has(string $name): bool
    {
        self::$registry ??= self::default();

        return isset(self::$registry[$name]);
    }

    public static function names(): array
    {
        self::$registry ??= self::default();

        return array_keys(self::$registry);
    }

    private static function default(): array
    {
        $iso = new IsoCalendarSystem();
        $gregorian = new GregorianCalendarSystem();

        return [
            $iso->name() => $iso,
            $gregorian->name() => $gregorian,
        ];
    }
}
