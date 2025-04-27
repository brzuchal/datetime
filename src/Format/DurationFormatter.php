<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Format\DurationFormatDefinition\IsoExtendedDurationFormatDefinition;
use Brzuchal\DateTime\Format\DurationFormatDefinition\IsoStandardDurationFormatDefinition;
use Brzuchal\DateTime\InvalidDuration;

final readonly class DurationFormatter
{
    public static function of(DurationFormat $format): self
    {
        return new self($format);
    }

    private DurationFormatDefinition $formatDefinition;

    public function __construct(DurationFormat $format = DurationFormat::IsoStandard)
    {
        $this->formatDefinition = match ($format) {
            DurationFormat::IsoStandard => new IsoStandardDurationFormatDefinition(),
            DurationFormat::IsoExtended => new IsoExtendedDurationFormatDefinition(),
        };
    }

    public function format(Duration $duration): string
    {
        return $this->formatDefinition->format($duration);
    }

    /**
     * @throws InvalidDuration
     */
    public function parse(string $text): Duration
    {
        return $this->formatDefinition->parse($text);
    }
}
