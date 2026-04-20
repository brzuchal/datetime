<?php

declare(strict_types=1);

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
                '/^T(?P<hours>\d{2}):(?P<minutes>\d{2}):(?P<seconds>\d{2}(?:[.,]\d{1,9})?)$/',
                $text,
                $matches,
                \PREG_UNMATCHED_AS_NULL,
            )
        ) {
            throw new InvalidDuration(sprintf('Invalid ISO 8601 extended duration string: %s', $text));
        }

        $secondsString = $matches['seconds'];
        [$seconds, $nanos] = self::parseSeconds($secondsString);

        return new Duration(
            hours: (int) $matches['hours'],
            minutes: (int) $matches['minutes'],
            seconds: $seconds,
            nanos: $nanos,
        );
    }

    public function format(Duration $duration): string
    {
        $seconds = \sprintf('%02d', $duration->seconds);
        if ($duration->nanos > 0) {
            $seconds .= '.' . \rtrim(\sprintf('%09d', $duration->nanos), '0');
        }

        return sprintf(
            'T%02d:%02d:%s',
            $duration->hours,
            $duration->minutes,
            $seconds,
        );
    }

    /**
     * @return array{0:int,1:int}
     *
     * @throws InvalidDuration
     */
    private static function parseSeconds(string $seconds): array
    {
        if ($seconds === '') {
            return [0, 0];
        }

        if (\strpbrk($seconds, '.,') === false) {
            return [(int) $seconds, 0];
        }

        $parts = \preg_split('/[.,]/', $seconds, 2);
        if ($parts === false || ! isset($parts[1])) {
            throw new InvalidDuration('Unable to parse fractional seconds from "' . $seconds . '".');
        }

        [$whole, $fraction] = $parts;

        $nanos = (int) \str_pad(\substr($fraction, 0, 9), 9, '0');

        return [(int) $whole, $nanos];
    }
}
