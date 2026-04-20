<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

/**
 * Strategy for resolving local date-time overlaps (when clocks move backward).
 */
enum OverlapPolicy
{
    /**
     * Prefers the earlier offset (typically DST).
     */
    case PreferEarlier;

    /**
     * Prefers the later offset (typically Standard Time).
     */
    case PreferLater;
}
