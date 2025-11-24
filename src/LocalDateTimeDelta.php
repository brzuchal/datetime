<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Stringable;

/**
 * A delta of time, consisting of a period and a duration.
 *
 * This class represents an amount of time defined by both calendar units (years, months, days)
 * and time units (hours, minutes, seconds, nanoseconds).
 *
 * @psalm-immutable
 */
final readonly class LocalDateTimeDelta implements Stringable
{
    public function __construct(
        public Period $period,
        public Duration $duration,
    ) {}

    public static function of(Period $period, Duration $duration): self
    {
        return new self($period, $duration);
    }

    public static function empty(): self
    {
        return new self(Period::zero(), new Duration());
    }

    /**
     * Obtains a LocalDateTimeDelta from a text string such as PnYnMnDTnHnMnS.
     *
     * @throws InvalidDuration
     */
    public static function parse(string $text): self
    {
        $components = Format\Iso8601Parser::parse($text);

        return new self(
            new Period(
                $components['years'],
                $components['months'],
                $components['days'],
            ),
            new Duration(
                $components['hours'],
                $components['minutes'],
                $components['seconds'],
                $components['nanos'],
            ),
        );
    }

    public function plus(self $other): self
    {
        return new self(
            $this->period->plusPeriod($other->period),
            $this->duration->plusDuration($other->duration),
        );
    }

    public function minus(self $other): self
    {
        return new self(
            $this->period->minusPeriod($other->period),
            $this->duration->minusDuration($other->duration),
        );
    }

    public function negated(): self
    {
        return new self(
            $this->period->negated(),
            $this->duration->negated(),
        );
    }

    public function multipliedBy(int $scalar): self
    {
        // Period no longer has multipliedBy, so we construct it manually if needed,
        // but we removed multipliedBy from Period because it was deemed useless.
        // However, for Delta, if we want to support backoff-like logic for the whole thing,
        // we might need it. But Period doesn't support it.
        // Let's skip multipliedBy for Period part or re-implement scalar multiplication for Period here?
        // User agreed to remove multipliedBy from Period.
        // So Delta::multipliedBy might only make sense for Duration part?
        // Or we just multiply fields of Period manually here.

        return new self(
            new Period(
                $this->period->years * $scalar,
                $this->period->months * $scalar,
                $this->period->days * $scalar,
            ),
            $this->duration->multipliedBy($scalar),
        );
    }

    public function __toString(): string
    {
        if ($this->period->isZero() && $this->duration->hours === 0 && $this->duration->minutes === 0 && $this->duration->seconds === 0 && $this->duration->nanos === 0) {
            return 'PT0S';
        }

        $periodString = (string) $this->period; // e.g. P1Y2M3D or P0D
        $durationString = (string) $this->duration; // e.g. PT4H5M6S or PT0S

        // If Period is zero (P0D), we might want to omit it if Duration is not zero.
        // But Period::__toString() returns P0D for zero.

        // Logic to combine:
        // Period: P1Y2M3D
        // Duration: PT4H5M6S
        // Result: P1Y2M3DT4H5M6S

        $p = \substr($periodString, 1); // remove leading P
        if ($this->period->isZero()) {
            $p = '';
        }

        $d = \substr($durationString, 2); // remove leading PT
        if ($durationString === 'PT0S') {
            $d = '';
        }

        if ($p === '' && $d === '') {
            return 'PT0S';
        }

        $out = 'P' . $p;
        if ($d !== '') {
            $out .= 'T' . $d;
        }

        return $out;
    }
}
