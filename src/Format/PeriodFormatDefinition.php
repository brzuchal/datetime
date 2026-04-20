<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\Period;

interface PeriodFormatDefinition
{
    public function format(Period $period): string;

    public function parse(string $text): Period;
}
