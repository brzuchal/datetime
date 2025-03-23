<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

interface TemporalAccessor
{
    public function get(TemporalField $field): int|null;

    public function has(TemporalField ...$fields): bool;
}
