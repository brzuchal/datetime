<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal\Adjusters;

use Brzuchal\DateTime\DayOfWeek;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\Temporal\Temporal;
use Brzuchal\DateTime\Temporal\TemporalAdjuster;

/**
 * Adjuster that shifts dates or date-times to the next/previous occurrence of a given day of week.
 */
final class DayOfWeekAdjuster implements TemporalAdjuster
{
    private function __construct(
        private DayOfWeek $target,
        private bool $strict,
        private bool $previous,
    ) {}

    public static function next(DayOfWeek $dayOfWeek): self
    {
        return new self($dayOfWeek, true, false);
    }

    public static function nextOrSame(DayOfWeek $dayOfWeek): self
    {
        return new self($dayOfWeek, false, false);
    }

    public static function previous(DayOfWeek $dayOfWeek): self
    {
        return new self($dayOfWeek, true, true);
    }

    public static function previousOrSame(DayOfWeek $dayOfWeek): self
    {
        return new self($dayOfWeek, false, true);
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
        $current = DayOfWeek::from($date->dayOfWeek);
        $delta = $this->computeDelta($current);

        return $date->plus(days: $delta);
    }

    private function adjustDateTime(LocalDateTime $dateTime): LocalDateTime
    {
        $adjustedDate = $this->adjustDate($dateTime->toLocalDate());

        if ($adjustedDate->equalTo($dateTime->toLocalDate())) {
            return $dateTime;
        }

        return LocalDateTime::ofDateAndTime($adjustedDate, $dateTime->toLocalTime());
    }

    private function computeDelta(DayOfWeek $current): int
    {
        $currentValue = $current->value;
        $targetValue = $this->target->value;

        if ($this->previous) {
            $difference = ($currentValue - $targetValue) % 7;
            if ($difference < 0) {
                $difference += 7;
            }

            if ($this->strict && $difference === 0) {
                $difference = 7;
            }

            return -$difference;
        }

        $difference = ($targetValue - $currentValue) % 7;
        if ($difference < 0) {
            $difference += 7;
        }

        if ($this->strict && $difference === 0) {
            $difference = 7;
        }

        return $difference;
    }
}
