<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

/**
 * Strategy for resolving local date-time gaps (when clocks move forward).
 */
enum GapPolicy
{
    /**
     * Shifts the local time forward by the gap duration.
     * For example, in a 1-hour gap at 02:00, 02:30 becomes 03:30.
     */
    case ShiftForward;

    /**
     * Throws an exception if the local time falls into a gap.
     */
    case Throw;
}
