<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

/**
 * Represents a period of time with a specified number of years, months, and days.
 */
final readonly class Period
{
    /**
     * Constructor method to initialize the class with years, months, and days.
     *
     * @param int $years The number of years, default is 0.
     * @param int $months The number of months, default is 0.
     * @param int $days The number of days, default is 0.
     */
    public function __construct(
        public int $years = 0,
        public int $months = 0,
        public int $days = 0,
    ) {}

    /**
     * Creates a new instance of the class with the specified years, months, and days.
     *
     * @param int $years The number of years to initialize. Default is 0.
     * @param int $months The number of months to initialize. Default is 0.
     * @param int $days The number of days to initialize. Default is 0.
     * @return self A new instance of the class initialized with the provided values.
     */
    public static function of(int $years = 0, int $months = 0, int $days = 0): self
    {
        return new self($years, $months, $days);
    }

    /**
     * Returns a new instance of the class with the sign of all components negated.
     *
     * @return self A new instance of the class with the years, months, and days inverted.
     */
    public function negate(): self
    {
        return new self(-$this->years, -$this->months, -$this->days);
    }
}
