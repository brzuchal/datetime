<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format\Pattern;

use Brzuchal\DateTime\Format\InvalidPattern;
use Brzuchal\DateTime\Format\UnsupportedPatternSymbol;

/**
 * @internal Compiles formatting patterns into token streams and regex metadata.
 */
final class PatternCompiler
{
    /** @var array<string, PatternSpecification> */
    private static array $cache = [];

    public static function compile(string $pattern): PatternSpecification
    {
        if (! \array_key_exists($pattern, self::$cache)) {
            self::$cache[$pattern] = self::compileInternal($pattern);
        }

        return self::$cache[$pattern];
    }

    private static function compileInternal(string $pattern): PatternSpecification
    {
        $index = 0;
        $hasField = false;
        $tokens = self::parseTokens($pattern, $index, false, $hasField);

        if ($index !== \strlen($pattern)) {
            throw new InvalidPattern('Pattern parsing did not consume entire input.');
        }

        if (! $hasField) {
            throw new InvalidPattern('Pattern must contain at least one formatting symbol.');
        }

        $captures = [];
        $captureIndex = 0;
        $regexBody = self::buildRegex($tokens, $captures, $captureIndex);
        $regex = '/^' . $regexBody . '$/';

        return new PatternSpecification($tokens, $regex, $captures);
    }

    /**
     * @param list<Token> $tokens
     * @param list<Capture> $captures
     */
    private static function buildRegex(array $tokens, array &$captures, int &$captureIndex): string
    {
        $regex = '';

        foreach ($tokens as $token) {
            if ($token instanceof LiteralToken) {
                $regex .= \preg_quote($token->value, '/');
                continue;
            }

            if ($token instanceof OptionalToken) {
                $inner = self::buildRegex($token->tokens, $captures, $captureIndex);
                $regex .= '(?:' . $inner . ')?';
                continue;
            }

            if ($token instanceof FieldToken) {
                [$segment, $capture] = self::regexForField($token->symbol, $token->length, $captureIndex);
                $regex .= $segment;
                if ($capture !== null) {
                    $captures[] = $capture;
                }

                continue;
            }

            throw new \LogicException('Unknown token type encountered while building regex.');
        }

        return $regex;
    }

    /**
     * @param non-empty-string $symbol
     * @param positive-int $length
     *
     * @return array{0: string, 1: Capture|null}
     */
    private static function regexForField(string $symbol, int $length, int &$captureIndex): array
    {
        $maxPrecision = $length > 9 ? 9 : $length;

        return match ($symbol) {
            'Y' => self::capture(++$captureIndex, '(?P<%s>\d{' . ($length < 4 ? 4 : $length) . ',})', 'Y', $length < 4 ? 4 : $length),
            'y' => self::capture(++$captureIndex, '(?P<%s>\d{2})', 'y', 2),
            'X' => self::capture(++$captureIndex, '(?P<%s>[+-]?\d{4,})', 'X', max($length, 4)),
            'm' => self::capture(++$captureIndex, '(?P<%s>\d{2})', 'm', 2),
            'n' => self::capture(++$captureIndex, '(?P<%s>\d{1,2})', 'n', 2),
            'd' => self::capture(++$captureIndex, '(?P<%s>\d{2})', 'd', 2),
            'j' => self::capture(++$captureIndex, '(?P<%s>\d{1,2})', 'j', 2),
            'H' => self::capture(++$captureIndex, '(?P<%s>[01]\d|2[0-3])', 'H', 2),
            'G' => self::capture(++$captureIndex, '(?P<%s>\d|1\d|2[0-3])', 'G', 2),
            'h' => self::capture(++$captureIndex, '(?P<%s>0[1-9]|1[0-2])', 'h', 2),
            'g' => self::capture(++$captureIndex, '(?P<%s>[1-9]|1[0-2])', 'g', 2),
            'i' => self::capture(++$captureIndex, '(?P<%s>[0-5]\d)', 'i', 2),
            's' => self::capture(++$captureIndex, '(?P<%s>[0-5]\d)', 's', 2),
            'f' => self::capture(++$captureIndex, '(?:\.(?P<%s>\d{1,' . max($maxPrecision, 1) . '}))', 'f', max($maxPrecision, 1)),
            'u' => self::capture(++$captureIndex, '(?P<%s>\d{6})', 'u', 6),
            'v' => self::capture(++$captureIndex, '(?P<%s>\d{3})', 'v', 3),
            'a', 'A' => self::capture(++$captureIndex, '(?P<%s>am|pm|AM|PM)', $symbol, 2),
            'z' => self::capture(++$captureIndex, '(?P<%s>\d{1,3})', 'z', 3),
            'o' => self::capture(++$captureIndex, '(?P<%s>-?\d{4,})', 'o', 4),
            'W' => self::capture(++$captureIndex, '(?P<%s>\d{1,2})', 'W', 2),
            'w' => self::capture(++$captureIndex, '(?P<%s>[0-6])', 'w', 1),
            'N' => self::capture(++$captureIndex, '(?P<%s>[1-7])', 'N', 1),
            'L' => ['(?:0|1)', null],
            't' => ['(?:28|29|30|31)', null],
            'S' => ['(?:st|nd|rd|th)', null],
            'D' => ['(?i:' . \implode('|', Vocabulary::WEEKDAY_NAMES_SHORT) . ')', null],
            'l' => ['(?i:' . \implode('|', Vocabulary::WEEKDAY_NAMES_FULL) . ')', null],
            'M' => ['(?i:' . \implode('|', Vocabulary::MONTH_NAMES_SHORT) . ')', null],
            'F' => ['(?i:' . \implode('|', Vocabulary::MONTH_NAMES_FULL) . ')', null],
            default => throw new UnsupportedPatternSymbol('Unsupported field symbol "' . $symbol . '".'),
        };
    }

    /**
     * @return list<Token>
     */
    private static function parseTokens(string $pattern, int &$index, bool $insideOptional, bool &$hasField): array
    {
        $tokens = [];
        $length = \strlen($pattern);

        while ($index < $length) {
            $char = $pattern[$index];

            if ($char === '\\') {
                ++$index;
                if ($index >= $length) {
                    $tokens[] = new LiteralToken('\\');
                } else {
                    $tokens[] = new LiteralToken($pattern[$index]);
                    ++$index;
                }

                continue;
            }

            if ($char === '[') {
                ++$index;
                $child = self::parseTokens($pattern, $index, true, $hasField);
                $tokens[] = new OptionalToken($child);
                continue;
            }

            if ($char === ']') {
                if (! $insideOptional) {
                    throw new InvalidPattern('Unexpected closing optional token at position ' . $index . '.');
                }

                ++$index;

                return $tokens;
            }

            if (\ctype_alpha($char) && \in_array($char, Vocabulary::SUPPORTED_FIELDS, true)) {
                $count = 1;
                while ($index + $count < $length && $pattern[$index + $count] === $char) {
                    ++$count;
                }

                $tokens[] = new FieldToken($char, $count);
                $hasField = true;
                $index += $count;
                continue;
            }

            if (\ctype_alpha($char)) {
                throw new UnsupportedPatternSymbol('Unsupported pattern symbol "' . $char . '" at position ' . $index . '.');
            }

            $literal = '';
            while ($index < $length) {
                $literalChar = $pattern[$index];
                if (
                    $literalChar === '\\'
                    || $literalChar === '['
                    || $literalChar === ']'
                    || \ctype_alpha($literalChar)
                ) {
                    break;
                }

                $literal .= $literalChar;
                ++$index;
            }

            if ($literal !== '') {
                $tokens[] = new LiteralToken($literal);
                continue;
            }

            throw new UnsupportedPatternSymbol('Unsupported character "' . $char . '" in pattern.');
        }

        if ($insideOptional) {
            throw new InvalidPattern('Optional section was not closed.');
        }

        return $tokens;
    }

    /**
     * @param non-empty-string $symbol
     * @param positive-int $length
     *
     * @return array{0: string, 1: Capture}
     */
    private static function capture(int $index, string $pattern, string $symbol, int $length): array
    {
        $name = 'c' . $index;
        $segment = \sprintf($pattern, $name);

        return [$segment, new Capture($name, $symbol, $length)];
    }
}
