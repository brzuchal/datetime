<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

final readonly class DateTimeFormatter
{
    private function __construct(private string $pattern)
    {}

    /** @throws UnsupportedPatternSymbol */
    public static function of(DateTimeFormat|string $pattern): self
    {
        if ($pattern instanceof DateTimeFormat) {
            return new self($pattern->value);
        }

        $normalizedPattern = \preg_replace('/[\s\-,]/', '', $pattern);
        if (! \preg_match('/^[yYumdHisah]+$/', $normalizedPattern)) {
            throw new UnsupportedPatternSymbol('Pattern contains unsupported symbols or invalid characters');
        }

        return new self($pattern);
    }

    /** @throws ParseException */
    public function parse(string $input): TemporalFields
    {
        $regex = $this->patternToRegex($this->pattern);
        if (! \preg_match($regex, $input, $matches)) {
            throw new ParseException("Unable to parse date/time from '{$input}'");
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
        $year = $accessor->get(TemporalField::Year) ?? 0;

        return \strtr($this->pattern, [
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
        ]);
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = \strtr(\preg_quote($pattern, '/'), [
            'y' => '(?P<year2>\d{2})',                   // two-digit year
            'Y' => '(?P<year4>\d{4})',                   // 4-digit year
            'u' => '(?P<year_full>[+-]?\d{4,})',         // full ISO 8601 year with sign
            'm' => '(?P<month>\d{2})',
            'd' => '(?P<day>\d{2})',
            'H' => '(?P<hour24>\d{2})',
            'h' => '(?P<hour12>\d{2})',
            'i' => '(?P<minute>\d{2})',
            's' => '(?P<second>\d{2})',
            'a' => '(?P<ampm>AM|PM|am|pm)',
        ]);

        return "/^{$regex}$/";
    }
}
