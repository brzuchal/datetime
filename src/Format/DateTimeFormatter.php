<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;

final readonly class DateTimeFormatter
{
    private function __construct(private string $pattern)
    {}

    public static function of(DateTimeFormat $format): self
    {
        return new self($format->value);
    }

    /**
     * @param non-empty-string $pattern The pattern to use for formatting and parsing.
     *
     * @throws InvalidPattern If the provided pattern is empty or null.
     * @throws UnsupportedPatternSymbol If the pattern contains unsupported symbols or invalid characters.
     */
    public static function fromPattern(string $pattern): self
    {
        $length = \strlen($pattern);
        $hasToken = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];

            if ($char === '\\') {
                $i++;
                continue;
            }

            if ($char === ' ' || $char === '-' || $char === ',' || $char === ':') {
                continue;
            }

            if (! \in_array($char, ['y', 'Y', 'u', 'm', 'd', 'H', 'h', 'i', 's', 'a'], true)) {
                throw new UnsupportedPatternSymbol('Pattern contains unsupported symbols or invalid characters');
            }

            $hasToken = true;
        }

        if (! $hasToken) {
            throw new InvalidPattern('Pattern cannot be empty');
        }

        return new self($pattern);
    }

    /** @throws InvalidInput */
    public function parse(string $input): TemporalFields
    {
        $regex = $this->patternToRegex($this->pattern);
        if (! \preg_match($regex, $input, $matches)) {
            throw new InvalidInput('Unable to parse date/time from "' . $input . '"');
        }

        $hour = isset($matches['hour24']) ? (int) $matches['hour24'] : null;
        if (isset($matches['hour12'], $matches['ampm'])) {
            $hour12 = (int) $matches['hour12'];
            $ampm = \strtolower($matches['ampm']);

            if ($ampm === 'pm' && $hour12 !== 12) {
                $hour = $hour12 + 12;
            } elseif ($ampm === 'am' && $hour12 === 12) {
                $hour = 0;
            } else {
                $hour = $hour12;
            }
        }

        $year = null;
        if (isset($matches['year_full'])) {
            $year = (int) $matches['year_full'];
        } elseif (isset($matches['year4'])) {
            $year = (int) $matches['year4'];
        } elseif (isset($matches['year2'])) {
            $year = (int) $matches['year2'];
            $year += $year >= 70 ? 1900 : 2000; // Simple century pivot
        }

        return new TemporalFields(
            year: $year,
            month: isset($matches['month']) ? (int) $matches['month'] : null,
            day: isset($matches['day']) ? (int) $matches['day'] : null,
            hour: $hour,
            minute: isset($matches['minute']) ? (int) $matches['minute'] : null,
            second: isset($matches['second']) ? (int) $matches['second'] : null,
        );
    }

    public function format(TemporalAccessor $accessor): string
    {
        $result = '';
        $length = \strlen($this->pattern);
        $year = $accessor->get(TemporalField::Year) ?? 0;

        for ($i = 0; $i < $length; $i++) {
            $char = $this->pattern[$i];

            if ($char === '\\') {
                $i++;
                $result .= $i < $length ? $this->pattern[$i] : '\\';

                continue;
            }

            $result .= match ($char) {
                'y' => \sprintf('%02d', $year % 100),
                'Y' => \sprintf('%04d', $year),
                'u' => $year >= 10000 || $year < 0
                    ? \sprintf('%+d', $year)
                    : \sprintf('%04d', $year),
                'm' => \sprintf('%02d', $accessor->get(TemporalField::Month) ?? 0),
                'd' => \sprintf('%02d', $accessor->get(TemporalField::Day) ?? 0),
                'H' => \sprintf('%02d', $accessor->get(TemporalField::Hour) ?? 0),
                'h' => \sprintf('%02d', ($accessor->get(TemporalField::Hour) ?? 0) % 12 ?: 12),
                'i' => \sprintf('%02d', $accessor->get(TemporalField::Minute) ?? 0),
                's' => \sprintf('%02d', $accessor->get(TemporalField::Second) ?? 0),
                'a' => ($accessor->get(TemporalField::Hour) ?? 0) >= 12 ? 'PM' : 'AM',
                default => $char,
            };
        }

        return $result;
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = '';
        $length = \strlen($pattern);
        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];

            if ($char === '\\') {
                $i++;
                if ($i < $length) {
                    $regex .= \preg_quote($pattern[$i], '/');
                } else {
                    $regex .= '\\\\';
                }

                continue;
            }

            $regex .= match ($char) {
                'y' => '(?P<year2>\\d{2})',
                'Y' => '(?P<year4>\\d{4})',
                'u' => '(?P<year_full>[+-]?\\d{4,})',
                'm' => '(?P<month>\\d{2})',
                'd' => '(?P<day>\\d{2})',
                'H' => '(?P<hour24>\\d{2})',
                'h' => '(?P<hour12>\\d{2})',
                'i' => '(?P<minute>\\d{2})',
                's' => '(?P<second>\\d{2})',
                'a' => '(?P<ampm>AM|PM|am|pm)',
                default => \preg_quote($char, '/'),
            };
        }

        return '/^' . $regex . '$/';
    }
}
