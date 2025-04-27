<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

abstract class Temporal implements TemporalAccessor
{
    abstract public function isUnitSupported(TemporalUnit $unit): bool;

    abstract public function withField(TemporalField $field, int $value): Temporal;

    public function addAmount(TemporalAmount $amount): Temporal
    {
        return $amount->addTo($this);
    }

    public function minusAmount(TemporalAmount $amount): Temporal
    {
        return $amount->subtractFrom($this);
    }
}
