<?php declare(strict_types=1);

namespace Brzuchal\DateTime\CalendarSystems;

/**
 * Represents an exception thrown when an invalid day of the year is encountered.
 *
 * This exception is typically used to indicate that a given day does not fall
 * within the acceptable range for days in a year.
 */
final class InvalidDayOfYear extends \Exception
{}
