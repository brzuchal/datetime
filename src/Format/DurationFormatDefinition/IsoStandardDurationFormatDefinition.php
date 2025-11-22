<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\DurationFormatDefinition;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\DurationFormatDefinition;
use Brzuchal\DateTime\Format\Iso8601Parser;
use Brzuchal\DateTime\InvalidDuration;

final readonly class IsoStandardDurationFormatDefinition implements DurationFormatDefinition
{
    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Duration
    {
        $components = Iso8601Parser::parse($text);

        if (
            $components['years'] !== 0
            || $components['months'] !== 0
            || $components['days'] !== 0
        ) {
            throw new InvalidDuration(sprintf('Duration string cannot contain date components: %s', $text));
        }

        return new Duration(
            hours: $components['hours'],
            minutes: $components['minutes'],
            seconds: $components['seconds'],
            nanos: $components['nanos'],
        );
    }

    public function format(Duration $duration): string
    {
        $seconds = (string) $duration->seconds;
        if ($duration->nanos > 0) {
            $seconds .= '.' . \rtrim(\sprintf('%09d', $duration->nanos), '0');
        }

        $buffer = 'PT';

        if ($duration->hours > 0) {
            $buffer .= $duration->hours . 'H';
        }
        if ($duration->minutes > 0) {
            $buffer .= $duration->minutes . 'M';
        }
        if ($duration->seconds > 0 || $duration->nanos > 0) {
            $buffer .= $seconds . 'S';
        }

        if ($buffer === 'PT') {
            return 'PT0S';
        }

        return $buffer;
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
