<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

abstract class TemporalAmount
{
    abstract public array $units { get; }

    abstract public function get(TemporalUnit $unit): int;

    abstract function addTo(Temporal $temporal): Temporal;

    abstract function subtractFrom(Temporal $temporal): Temporal;
}
