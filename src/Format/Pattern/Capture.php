<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\Pattern;

final readonly class Capture
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $symbol
     * @param positive-int $length
     */
    public function __construct(
        public string $name,
        public string $symbol,
        public int $length,
    ) {
    }
}
