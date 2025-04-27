<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

use Brzuchal\DateTime\Temporal\TemporalField;

interface TemporalAccessor
{
    public function get(TemporalField $field): int|null;

    public function supports(TemporalField ...$fields): bool;
}
