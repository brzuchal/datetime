<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;

final readonly class TemporalFields implements TemporalAccessor
{
    public function __construct(
        public int|null $year = null,
        public int|null $month = null,
        public int|null $day = null,
        public int|null $hour = null,
        public int|null $minute = null,
        public int|null $second = null,
        public int|null $nano = null,
    ) {}

    public function get(TemporalField $field): int|null
    {
        return match ($field) {
            TemporalField::Year => $this->year,
            TemporalField::Month => $this->month,
            TemporalField::Day => $this->day,
            TemporalField::Hour => $this->hour,
            TemporalField::Minute => $this->minute,
            TemporalField::Second => $this->second,
            TemporalField::Nano => $this->nano,
            default => null,
        };
    }

    public function supports(TemporalField ...$fields): bool
    {
        foreach ($fields as $field) {
            if ($this->get($field) !== null) {
                continue;
            }

            return false;
        }

        return true;
    }

    public function query(callable $query): mixed
    {
        return $query($this);
    }
}
