<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\Pattern;

final readonly class LiteralToken implements Token
{
    /**
     * @param non-empty-string $value
     */
    public function __construct(
        public string $value,
    ) {}
}
