<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\Pattern;

final readonly class FieldToken implements Token
{
    /**
     * @param non-empty-string $symbol
     * @param positive-int $length
     */
    public function __construct(
        public string $symbol,
        public int $length,
    ) {
    }
}
