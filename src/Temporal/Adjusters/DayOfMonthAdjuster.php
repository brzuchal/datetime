<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal\Adjusters;

use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\Temporal\Temporal;
use Brzuchal\DateTime\Temporal\TemporalAdjuster;

final class DayOfMonthAdjuster implements TemporalAdjuster
{
    private function __construct(private bool $first)
    {}

    public static function first(): self
    {
        return new self(true);
    }

    public static function last(): self
    {
        return new self(false);
    }

    public function adjust(Temporal $temporal): Temporal
    {
        return match (true) {
            $temporal instanceof LocalDate => $this->adjustDate($temporal),
            $temporal instanceof LocalDateTime => $this->adjustDateTime($temporal),
            $temporal instanceof LocalTime => $temporal,
            default => throw new \InvalidArgumentException('Adjuster cannot handle temporal of type ' . $temporal::class),
        };
    }

    private function adjustDate(LocalDate $date): LocalDate
    {
        if ($this->first) {
            return LocalDate::of($date->year, $date->month, 1);
        }

        $firstOfMonth = LocalDate::of($date->year, $date->month, 1);

        return $firstOfMonth->plus(months: 1)->minus(days: 1);
    }

    private function adjustDateTime(LocalDateTime $dateTime): LocalDateTime
    {
        $adjustedDate = $this->adjustDate($dateTime->toLocalDate());

        if ($adjustedDate->equalTo($dateTime->toLocalDate())) {
            return $dateTime;
        }

        return LocalDateTime::ofDateAndTime($adjustedDate, $dateTime->toLocalTime());
    }
}
