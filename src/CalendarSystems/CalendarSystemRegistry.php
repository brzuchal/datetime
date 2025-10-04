<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

/**
 * A registry for managing instances of CalendarSystem.
 * Provides functionality to register, retrieve, and query available calendar systems by name.
 */
final class CalendarSystemRegistry
{
    /** @var array<non-empty-string,CalendarSystem> */
    private static array $registry;

    /**
     * Registers a calendar system into the internal registry.
     *
     * @param CalendarSystem $calendar The calendar system to be registered.
     */
    public static function register(CalendarSystem $calendar): void
    {
        self::$registry ??= self::default();

        self::$registry[$calendar->name()] = $calendar;
    }

    /**
     * Retrieves the calendar system associated with the given name.
     *
     * @param string $name The name of the calendar system to retrieve.
     * @return CalendarSystem The calendar system corresponding to the provided name.
     * @throws UnknownCalendarSystem If the calendar system with the specified name does not exist.
     */
    public static function get(string $name): CalendarSystem
    {
        self::$registry ??= self::default();

        return self::$registry[$name]
            ?? throw new UnknownCalendarSystem("Unknown calendar system: $name");
    }

    /**
     * Checks if the calendar system with the given name exists.
     *
     * @param string $name The name of the calendar system to check.
     * @return bool True if the calendar system with the specified name exists, otherwise false.
     */
    public static function has(string $name): bool
    {
        self::$registry ??= self::default();

        return isset(self::$registry[$name]);
    }

    /**
     * Retrieves the names of all registered items in the registry.
     *
     * @return list<non-empty-string> An array containing the keys of the registry.
     */
    public static function names(): array
    {
        self::$registry ??= self::default();

        return \array_keys(self::$registry);
    }

    /**
     * Provides the default set of calendar systems.
     *
     * @return array<non-empty-string,CalendarSystem> An associative array where the keys are the names of the calendar systems
     *                              and the values are the corresponding calendar system instances.
     */
    private static function default(): array
    {
        $iso = new IsoCalendarSystem();

        return [$iso->name() => $iso];
    }
}
