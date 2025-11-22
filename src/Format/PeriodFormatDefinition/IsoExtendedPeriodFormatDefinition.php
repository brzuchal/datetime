<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\PeriodFormatDefinition;

use Brzuchal\DateTime\Format\PeriodFormatDefinition;
use Brzuchal\DateTime\InvalidDuration;
use Brzuchal\DateTime\Period;

final readonly class IsoExtendedPeriodFormatDefinition implements PeriodFormatDefinition
{
    public function format(Period $period): string
    {
        return \sprintf(
            'P%04d-%02d-%02d',
            $period->years,
            $period->months,
            $period->days,
        );
    }

    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Period
    {
        if (
            ! \preg_match(
                '/^P(?P<years>-?\d{4})-(?P<months>-?\d{2})-(?P<days>-?\d{2})$/',
                $text,
                $matches,
                \PREG_UNMATCHED_AS_NULL,
            )
        ) {
            throw new InvalidDuration(\sprintf('Invalid ISO 8601 extended period string: %s', $text));
        }

        return new Period(
            (int) $matches['years'],
            (int) $matches['months'],
            (int) $matches['days'],
        );
    }
}
