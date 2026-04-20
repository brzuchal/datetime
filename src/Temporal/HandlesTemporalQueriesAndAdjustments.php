<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

/**
 * Shared implementations of {@see TemporalAccessor::query()} and {@see Temporal::adjust()}.
 */
trait HandlesTemporalQueriesAndAdjustments
{
    public function query(callable $query): mixed
    {
        return $query($this);
    }

    public function adjust(TemporalAdjuster $adjuster): static
    {
        $result = $adjuster->adjust($this);
        if (! $result instanceof static) {
            throw new \UnexpectedValueException(\sprintf(
                'Temporal adjuster for %s must return %s, got %s.',
                static::class,
                static::class,
                $result::class,
            ));
        }

        return $result;
    }
}
