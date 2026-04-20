<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\InvalidDuration;

interface DurationFormatDefinition
{
    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Duration;

    public function format(Duration $duration): string;
}
