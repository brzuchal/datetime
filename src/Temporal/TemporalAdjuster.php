<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

/**
 * Strategy capable of adjusting temporal objects according to custom rules.
 */
interface TemporalAdjuster
{
    public function adjust(Temporal $temporal): Temporal;
}
