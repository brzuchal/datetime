<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\Pattern;

final readonly class PatternSpecification
{
    /**
     * @param list<Token> $tokens
     * @param list<Capture> $captures
     */
    public function __construct(
        public array $tokens,
        public string $regex,
        public array $captures,
    ) {
    }
}
