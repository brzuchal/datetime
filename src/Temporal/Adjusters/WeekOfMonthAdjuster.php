<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal\Adjusters;

use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\Temporal\Temporal;
use Brzuchal\DateTime\Temporal\TemporalAdjuster;

final class WeekOfMonthAdjuster implements TemporalAdjuster
{
    private const string MODE_FIRST = 'first';
    private const string MODE_LAST = 'last';
    private const string MODE_NEXT = 'next';
    private const string MODE_PREVIOUS = 'previous';

    private function __construct(private string $mode)
    {
    }

    public static function firstWeek(): self
    {
        return new self(self::MODE_FIRST);
    }

    public static function lastWeek(): self
    {
        return new self(self::MODE_LAST);
    }

    public static function nextWeek(): self
    {
        return new self(self::MODE_NEXT);
    }

    public static function previousWeek(): self
    {
        return new self(self::MODE_PREVIOUS);
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
        return match ($this->mode) {
            self::MODE_FIRST => $this->firstWeekStart($date),
            self::MODE_LAST => $this->lastWeekStart($date),
            self::MODE_NEXT => $this->nextWeekStart($date),
            self::MODE_PREVIOUS => $this->previousWeekStart($date),
            default => $date,
        };
    }

    private function adjustDateTime(LocalDateTime $dateTime): LocalDateTime
    {
        $adjustedDate = $this->adjustDate($dateTime->toLocalDate());

        if ($adjustedDate->equalTo($dateTime->toLocalDate())) {
            return $dateTime;
        }

        return LocalDateTime::ofDateAndTime($adjustedDate, $dateTime->toLocalTime());
    }

    private function firstWeekStart(LocalDate $date): LocalDate
    {
        $first = LocalDate::of($date->year, $date->month, 1);
        $offset = $first->dayOfWeek === 0 ? 0 : 7 - $first->dayOfWeek;

        return $first->plus(days: $offset);
    }

    private function lastWeekStart(LocalDate $date): LocalDate
    {
        $last = LocalDate::of($date->year, $date->month, 1)->plus(months: 1)->minus(days: 1);

        return $this->weekStartWithinMonth($last);
    }

    private function nextWeekStart(LocalDate $date): LocalDate
    {
        $currentStart = $this->weekStartWithinMonth($date);
        $candidate = $currentStart->plus(days: 7);

        if ($candidate->month !== $date->month) {
            return $this->lastWeekStart($date);
        }

        return $candidate;
    }

    private function previousWeekStart(LocalDate $date): LocalDate
    {
        $currentStart = $this->weekStartWithinMonth($date);
        $candidate = $currentStart->minus(days: 7);

        if ($candidate->month !== $date->month) {
            return $this->firstWeekStart($date);
        }

        return $candidate;
    }

    private function weekStartWithinMonth(LocalDate $date): LocalDate
    {
        $start = $date->minus(days: $date->dayOfWeek);

        if ($start->month !== $date->month) {
            $start = $start->plus(days: 7);
        }

        return $start;
    }
}
