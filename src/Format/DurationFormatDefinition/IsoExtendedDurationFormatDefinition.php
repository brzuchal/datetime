<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\DurationFormatDefinition;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\DurationFormatDefinition;
use Brzuchal\DateTime\InvalidDuration;

final readonly class IsoExtendedDurationFormatDefinition implements DurationFormatDefinition
{
    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Duration
    {
        if (
            ! \preg_match(
                '/^P(?P<years>\d{4})-(?P<months>\d{2})-(?P<days>\d{2})T(?P<hours>\d{2}):(?P<minutes>\d{2}):(?P<seconds>\d{2})$/',
                $text,
                $matches,
            )
        ) {
            throw new InvalidDuration(sprintf('Invalid ISO 8601 extended duration string: %s', $text));
        }

        return new Duration(
            (int) $matches['years'],
            (int) $matches['months'],
            (int) $matches['days'],
            (int) $matches['hours'],
            (int) $matches['minutes'],
            (int) $matches['seconds'],
        );
    }

    public function format(Duration $duration): string
    {
        return sprintf(
            'P%04d-%02d-%02dT%02d:%02d:%02d',
            $duration->years,
            $duration->months,
            $duration->days,
            $duration->hours,
            $duration->minutes,
            $duration->seconds,
        );
    }
}
