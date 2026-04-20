<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Format\Pattern;

final readonly class OptionalToken implements Token
{
    /**
     * @param list<Token> $tokens
     */
    public function __construct(
        public array $tokens,
    ) {
    }
}
