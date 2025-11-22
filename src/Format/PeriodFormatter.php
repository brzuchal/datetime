<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\Format\PeriodFormatDefinition\IsoExtendedPeriodFormatDefinition;
use Brzuchal\DateTime\Format\PeriodFormatDefinition\IsoStandardPeriodFormatDefinition;
use Brzuchal\DateTime\InvalidDuration;
use Brzuchal\DateTime\Period;

final readonly class PeriodFormatter
{
    public static function of(PeriodFormat $format): self
    {
        return new self($format);
    }

    private PeriodFormatDefinition $formatDefinition;

    public function __construct(PeriodFormat $format = PeriodFormat::IsoStandard)
    {
        $this->formatDefinition = match ($format) {
            PeriodFormat::IsoStandard => new IsoStandardPeriodFormatDefinition(),
            PeriodFormat::IsoExtended => new IsoExtendedPeriodFormatDefinition(),
        };
    }

    public function format(Period $period): string
    {
        return $this->formatDefinition->format($period);
    }

    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Period
    {
        return $this->formatDefinition->parse($text);
    }
}
