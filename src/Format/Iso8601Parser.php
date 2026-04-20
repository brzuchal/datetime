<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\InvalidDuration;

/**
 * Internal helper for parsing ISO-8601 duration strings.
 *
 * @internal
 */
final class Iso8601Parser
{
    /**
     * Parses an ISO-8601 duration string into its components.
     *
     * @return array{
     *     years: int,
     *     months: int,
     *     days: int,
     *     hours: int,
     *     minutes: int,
     *     seconds: int,
     *     nanos: int
     * }
     * @throws InvalidDuration
     */
    public static function parse(string $text): array
    {
        if (
            !preg_match(
                '/^P
                (?:(?P<years>-?\d+(?:[.,]\d+)?)Y)?
                (?:(?P<months>-?\d+(?:[.,]\d+)?)M)?
                (?:(?P<days>-?\d+(?:[.,]\d+)?)D)?
                (?:T
                    (?:(?P<hours>-?\d+(?:[.,]\d+)?)H)?
                    (?:(?P<minutes>-?\d+(?:[.,]\d+)?)M)?
                    (?:(?P<seconds>-?\d+(?:[.,]\d+)?)S)?
                )?
            $/x',
                $text,
                $matches,
                \PREG_UNMATCHED_AS_NULL,
            )
        ) {
            throw new InvalidDuration(sprintf('Invalid ISO 8601 duration string: %s', $text));
        }

        if ($matches[0] === 'P') {
             throw new InvalidDuration('Duration string cannot be just \'P\'.');
        }

        foreach (['years', 'months', 'days', 'hours', 'minutes'] as $component) {
            $value = $matches[$component];
            if ($value === null) {
                continue;
            }

            if (\strpbrk($value, '.,') !== false) {
                throw new InvalidDuration(\sprintf('Fractional %s component is not supported: %s', $component, $value));
            }
        }

        $secondsString = $matches['seconds'] ?? null;
        $seconds = 0;
        $nanos = 0;

        if ($secondsString !== null) {
            if (\str_contains($secondsString, '.')) {
                [$s, $n] = \explode('.', $secondsString, 2);
                $seconds = (int) $s;
                $nanos = (int) \str_pad($n, 9, '0', \STR_PAD_RIGHT);
            } elseif (\str_contains($secondsString, ',')) {
                [$s, $n] = \explode(',', $secondsString, 2);
                $seconds = (int) $s;
                $nanos = (int) \str_pad($n, 9, '0', \STR_PAD_RIGHT);
            } else {
                $seconds = (int) $secondsString;
            }
        }

        return [
            'years' => (int) ($matches['years'] ?? 0),
            'months' => (int) ($matches['months'] ?? 0),
            'days' => (int) ($matches['days'] ?? 0),
            'hours' => (int) ($matches['hours'] ?? 0),
            'minutes' => (int) ($matches['minutes'] ?? 0),
            'seconds' => $seconds,
            'nanos' => $nanos,
        ];
    }
}
