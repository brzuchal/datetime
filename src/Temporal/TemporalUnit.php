<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalTime;

abstract class TemporalUnit
{
    abstract public function getDuration(): Duration;

    abstract public function isDateBased(): bool;

    abstract public function isTimeBased(): bool;

    public function isSupportedBy(Temporal $temporal): bool
    {
        if ($temporal instanceof LocalTime) {
            return $this->isTimeBased();
        }

        if ($temporal instanceof LocalDate) {
            return $this->isDateBased();
        }

        try {
            $temporal->plus(1, $this);
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    abstract public function addTo(Temporal $temporal, int $amount): Temporal;
}
