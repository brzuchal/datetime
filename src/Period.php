<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Format\PeriodFormat;
use Brzuchal\DateTime\Format\PeriodFormatter;

/**
 * Immutable period representing an amount of time in calendar units: years, months, and days.
 *
 * Represents a date-based amount of time in the ISO-8601 calendar system,
 * such as "2 years, 3 months and 4 days". Unlike {@see Duration}, which represents
 * time-based amounts (seconds/nanos), Period represents calendar-based amounts.
 *
 * Example usage:
 * <code>
 * $period = Period::of(years: 1, months: 2, days: 10);
 * $date = LocalDate::of(2025, 1, 15);
 * $futureDate = $date->plusPeriod($period); // 2026-03-25
 * </code>
 *
 * @see Duration For time-based amounts (hours, minutes, seconds, nanos)
 */
final readonly class Period
{
    /**
     * Creates a period with the specified years, months, and days.
     *
     * @param int $years  Number of years (can be negative).
     * @param int $months Number of months (can be negative).
     * @param int $days   Number of days (can be negative).
     */
    public function __construct(
        public int $years = 0,
        public int $months = 0,
        public int $days = 0,
    ) {}

    /**
     * Creates a period with the specified years, months, and days.
     *
     * Example:
     * <code>
     * $period = Period::of(years: 2, months: 6, days: 15);
     * </code>
     *
     * @param int $years  Number of years.
     * @param int $months Number of months.
     * @param int $days   Number of days.
     *
     * @return self The created period.
     */
    public static function of(int $years = 0, int $months = 0, int $days = 0): self
    {
        return new self($years, $months, $days);
    }

    /**
     * Creates a period representing a number of years.
     *
     * @param int $years Number of years.
     *
     * @return self Period of the specified years.
     */
    public static function ofYears(int $years): self
    {
        return new self(years: $years);
    }

    /**
     * Creates a period representing a number of months.
     *
     * @param int $months Number of months.
     *
     * @return self Period of the specified months.
     */
    public static function ofMonths(int $months): self
    {
        return new self(months: $months);
    }

    /**
     * Creates a period representing a number of days.
     *
     * @param int $days Number of days.
     *
     * @return self Period of the specified days.
     */
    public static function ofDays(int $days): self
    {
        return new self(days: $days);
    }

    /**
     * Creates a zero-length period.
     *
     * @return self Period of zero length.
     */
    public static function zero(): self
    {
        return new self();
    }

    /**
     * Checks if this period is zero length.
     *
     * @return bool True if this period has zero years, months and days.
     */
    public function isZero(): bool
    {
        return $this->years === 0 && $this->months === 0 && $this->days === 0;
    }

    /**
     * Checks if any of the units of this period are negative.
     *
     * @return bool True if any unit is negative.
     */
    public function isNegative(): bool
    {
        return $this->years < 0 || $this->months < 0 || $this->days < 0;
    }

    /**
     * Returns a copy of this period with the specified amount added.
     *
     * @param Period $period The period to add.
     *
     * @return self A new period with the result.
     */
    public function plusPeriod(Period $period): self
    {
        if ($period->isZero()) {
            return $this;
        }

        return new self(
            years: $this->years + $period->years,
            months: $this->months + $period->months,
            days: $this->days + $period->days,
        );
    }

    /**
     * Returns a copy of this period with the specified amount added.
     *
     * @param int $years  The number of years to add.
     * @param int $months The number of months to add.
     * @param int $days   The number of days to add.
     *
     * @return self A new period with the result.
     */
    public function plus(int $years = 0, int $months = 0, int $days = 0): self
    {
        if ($years === 0 && $months === 0 && $days === 0) {
            return $this;
        }

        return new self(
            years: $this->years + $years,
            months: $this->months + $months,
            days: $this->days + $days,
        );
    }

    /**
     * Returns a copy of this period with the specified years added.
     *
     * @param int $years Years to add (can be negative).
     *
     * @return self A new period with the specified years added.
     */
    public function plusYears(int $years): self
    {
        if ($years === 0) {
            return $this;
        }

        return new self(
            years: $this->years + $years,
            months: $this->months,
            days: $this->days,
        );
    }

    /**
     * Returns a copy of this period with the specified months added.
     *
     * @param int $months Months to add (can be negative).
     *
     * @return self A new period with the specified months added.
     */
    public function plusMonths(int $months): self
    {
        if ($months === 0) {
            return $this;
        }

        return new self(
            years: $this->years,
            months: $this->months + $months,
            days: $this->days,
        );
    }

    /**
     * Returns a copy of this period with the specified days added.
     *
     * @param int $days Days to add (can be negative).
     *
     * @return self A new period with the specified days added.
     */
    public function plusDays(int $days): self
    {
        if ($days === 0) {
            return $this;
        }

        return new self(
            years: $this->years,
            months: $this->months,
            days: $this->days + $days,
        );
    }

    /**
     * Returns a copy of this period with the specified amount subtracted.
     *
     * @param Period $period The period to subtract.
     *
     * @return self A new period with the result.
     */
    public function minusPeriod(Period $period): self
    {
        return $this->plusPeriod($period->negated());
    }

    /**
     * Returns a copy of this period with the specified amount subtracted.
     *
     * @param int $years  The number of years to subtract.
     * @param int $months The number of months to subtract.
     * @param int $days   The number of days to subtract.
     *
     * @return self A new period with the result.
     */
    public function minus(int $years = 0, int $months = 0, int $days = 0): self
    {
        return $this->plus(-$years, -$months, -$days);
    }

    /**
     * Returns a copy of this period with the specified years subtracted.
     *
     * @param int $years Years to subtract (can be negative).
     *
     * @return self A new period with the specified years subtracted.
     */
    public function minusYears(int $years): self
    {
        return $this->plusYears(-$years);
    }

    /**
     * Returns a copy of this period with the specified months subtracted.
     *
     * @param int $months Months to subtract (can be negative).
     *
     * @return self A new period with the specified months subtracted.
     */
    public function minusMonths(int $months): self
    {
        return $this->plusMonths(-$months);
    }

    /**
     * Returns a copy of this period with the specified days subtracted.
     *
     * @param int $days Days to subtract (can be negative).
     *
     * @return self A new period with the specified days subtracted.
     */
    public function minusDays(int $days): self
    {
        return $this->plusDays(-$days);
    }

    /**
     * Returns a copy of this period with the amounts negated.
     *
     * @return self A new period with the result.
     */
    public function negated(): self
    {
        return new self(
            years: -$this->years,
            months: -$this->months,
            days: -$this->days,
        );
    }

    /**
     * Returns a copy of this period with the years and months normalized.
     *
     * Normalization converts excess months into years (e.g., 18 months becomes 1 year and 6 months).
     *
     * Example:
     * <code>
     * $period = Period::of(years: 1, months: 15, days: 3);
     * $normalized = $period->normalized(); // 2 years, 3 months, 3 days
     * </code>
     *
     * @return self A normalized copy of this period.
     */
    public function normalized(): self
    {
        $totalMonths = $this->years * 12 + $this->months;
        $normalizedYears = \intdiv($totalMonths, 12);
        $normalizedMonths = $totalMonths % 12;

        if ($normalizedYears === $this->years && $normalizedMonths === $this->months) {
            return $this;
        }

        return new self(
            years: $normalizedYears,
            months: $normalizedMonths,
            days: $this->days,
        );
    }

    /**
     * Gets the total number of months in this period.
     *
     * This returns the total number of months represented by the years and months fields.
     * Days are not included in the calculation.
     *
     * @return int The total number of months.
     */
    public function toTotalMonths(): int
    {
        return $this->years * 12 + $this->months;
    }

    /**
     * Checks if this period is equal to another period.
     *
     * @param Period $other The other period to compare to.
     *
     * @return bool True if this period equals the other period.
     */
    public function equals(Period $other): bool
    {
        return $this->years === $other->years
            && $this->months === $other->months
            && $this->days === $other->days;
    }

    /**
     * Returns an ISO-8601 representation of this period.
     *
     * The format is "PnYnMnD" where n represents the number of years, months, and days.
     * Zero values may be omitted. If all values are zero, "P0D" is returned.
     *
     * Examples:
     * - P2Y3M4D (2 years, 3 months, 4 days)
     * - P1Y (1 year)
     * - P6M (6 months)
     * - P15D (15 days)
     * - P0D (zero period)
     *
     * @return string ISO-8601 period format.
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * Formats the period using the specified formatting style.
     *
     * @param PeriodFormat $format The formatting style to apply. Default is PeriodFormat::IsoStandard.
     *
     * @return string The formatted period as a string.
     */
    public function format(PeriodFormat $format = PeriodFormat::IsoStandard): string
    {
        return PeriodFormatter::of($format)->format($this);
    }

    /**
     * Serializes this period.
     *
     * @return array{value: string}
     */
    public function __serialize(): array
    {
        return ['value' => (string) $this];
    }

    /**
     * Deserializes this period.
     *
     * @param array<string,mixed> $data Serialized data.
     */
    public function __unserialize(array $data): void
    {
        if (! \array_key_exists('value', $data) || ! \is_string($data['value'])) {
            throw new \UnexpectedValueException(\sprintf('Serialized %s payload is malformed.', self::class));
        }

        $period = self::parse($data['value']);

        self::__construct(
            years: $period->years,
            months: $period->months,
            days: $period->days,
        );
    }

    /**
     * Parses an ISO-8601 period string.
     *
     * The format is "PnYnMnD" where n represents numbers.
     *
     * Examples:
     * - "P2Y" (2 years)
     * - "P3M" (3 months)
     * - "P4D" (4 days)
     * - "P1Y2M3D" (1 year, 2 months, 3 days)
     * - "P0D" (zero period)
     *
     * @param string $text   The ISO-8601 period string to parse.
     * @param PeriodFormat $format The format to use for parsing. Default is PeriodFormat::IsoStandard.
     *
     * @return self The parsed period.
     *
     * @throws InvalidDuration If the string cannot be parsed.
     */
    public static function parse(string $text, PeriodFormat $format = PeriodFormat::IsoStandard): self
    {
        return PeriodFormatter::of($format)->parse($text);
    }
}
