<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\DurationFormatDefinition;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\DurationFormatDefinition;
use Brzuchal\DateTime\InvalidDuration;

final readonly class IsoStandardDurationFormatDefinition implements DurationFormatDefinition
{
    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Duration
    {
        if (!preg_match(
            '/^P
                (?:(?P<years>\d+(?:[.,]\d+)?)Y)?
                (?:(?P<months>\d+(?:[.,]\d+)?)M)?
                (?:(?P<days>\d+(?:[.,]\d+)?)D)?
                (?:T
                    (?:(?P<hours>\d+(?:[.,]\d+)?)H)?
                    (?:(?P<minutes>\d+(?:[.,]\d+)?)M)?
                    (?:(?P<seconds>\d+(?:[.,]\d+)?)S)?
                )?
            $/x',
            $text,
            $matches
        )) {
            throw new InvalidDuration(sprintf('Invalid ISO 8601 duration string: %s', $text));
        }

        $years = (int) ($matches['years'] ?? 0);
        $months = (int) ($matches['months'] ?? 0);
        $days = (int) ($matches['days'] ?? 0);
        $hours = (int) ($matches['hours'] ?? 0);
        $minutes = (int) ($matches['minutes'] ?? 0);
        [$seconds, $nanos] = self::parseSecondsAndNanos($matches['seconds'] ?? null);

        return new Duration($years, $months, $days, $hours, $minutes, $seconds, $nanos);
    }

    public function format(Duration $duration): string
    {
        $out = 'P';
        if ($duration->years) {
            $out .= $duration->years . 'Y';
        }

        if ($duration->months) {
            $out .= $duration->months . 'M';
        }

        if ($duration->days) {
            $out .= $duration->days . 'D';
        }

        $time = '';
        if ($duration->hours) {
            $time .= $duration->hours . 'H';
        }

        if ($duration->minutes) {
            $time .= $duration->minutes . 'M';
        }

        if ($duration->seconds || $duration->nanos) {
            $sec = (string) $duration->seconds;
            if ($duration->nanos > 0) {
                $sec .= '.' . \rtrim(\sprintf('%09d', $duration->nanos), '0');
            }

            $time .= $sec . 'S';
        }

        return $time ? $out . 'T' . $time : ($out === 'P' ? 'PT0S' : $out);
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function parseSecondsAndNanos(string|null $value): array
    {
        if ($value === null) {
            return [0, 0];
        }

        if (\str_contains($value, '.') || \str_contains($value, ',')) {
            $parts = \preg_split('/[.,]/', $value, 2);
            assert($parts !== false);
            [$sec, $frac] = $parts;
            $nanos = (int) \str_pad(\substr($frac, 0, 9), 9, '0');
            return [(int) $sec, $nanos];
        }

        return [(int) $value, 0];
    }
}
