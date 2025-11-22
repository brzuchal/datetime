<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\PeriodFormatDefinition;

use Brzuchal\DateTime\Format\Iso8601Parser;
use Brzuchal\DateTime\Format\PeriodFormatDefinition;
use Brzuchal\DateTime\InvalidDuration;
use Brzuchal\DateTime\Period;

final readonly class IsoStandardPeriodFormatDefinition implements PeriodFormatDefinition
{
    public function format(Period $period): string
    {
        if ($period->years === 0 && $period->months === 0 && $period->days === 0) {
            return 'P0D';
        }

        $result = 'P';

        if ($period->years !== 0) {
            $result .= $period->years . 'Y';
        }

        if ($period->months !== 0) {
            $result .= $period->months . 'M';
        }

        if ($period->days !== 0) {
            $result .= $period->days . 'D';
        }

        return $result;
    }

    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Period
    {
        $components = Iso8601Parser::parse($text);

        if (
            $components['hours'] !== 0
            || $components['minutes'] !== 0
            || $components['seconds'] !== 0
            || $components['nanos'] !== 0
        ) {
            throw new InvalidDuration(sprintf('Period string cannot contain time components: %s', $text));
        }

        return new Period(
            years: $components['years'],
            months: $components['months'],
            days: $components['days'],
        );
    }
}
