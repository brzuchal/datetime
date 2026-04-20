<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

use Brzuchal\DateTime\Format\Pattern\Capture;
use Brzuchal\DateTime\Format\Pattern\FieldToken;
use Brzuchal\DateTime\Format\Pattern\LiteralToken;
use Brzuchal\DateTime\Format\Pattern\OptionalToken;
use Brzuchal\DateTime\Format\Pattern\PatternCompiler;
use Brzuchal\DateTime\Format\Pattern\PatternSpecification;
use Brzuchal\DateTime\Format\Pattern\Token;
use Brzuchal\DateTime\Format\Pattern\Vocabulary;
use Brzuchal\DateTime\Internal\IsoCalendar;
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\Temporal\TemporalAccessor;
use Brzuchal\DateTime\Temporal\TemporalField;

final readonly class DateTimeFormatter
{
    /** @var list<Token> */
    private array $tokens;
    /** @var list<Capture> */
    private array $captures;
    private string $regex;

    private function __construct(
        private string $pattern,
        PatternSpecification $specification,
    ) {
        $this->tokens = $specification->tokens;
        $this->regex = $specification->regex;
        $this->captures = $specification->captures;
    }

    public static function of(DateTimeFormat $format): self
    {
        return self::create($format->value);
    }

    /**
     * @param non-empty-string $pattern
     *
     * @throws InvalidPattern
     * @throws UnsupportedPatternSymbol
     */
    public static function fromPattern(string $pattern): self
    {
        if (\trim($pattern) === '') {
            throw new InvalidPattern('Pattern cannot be empty');
        }

        return self::create($pattern);
    }

    /** @throws InvalidFormat */
    public function parse(string $input): TemporalFields
    {
        if (! \preg_match($this->regex, $input, $matches)) {
            throw new InvalidFormat('Unable to parse date/time from "' . $input . '"');
        }

        $year = null;
        $twoDigitYear = null;
        $month = null;
        $day = null;
        $hour24 = null;
        $hour12 = null;
        $minute = null;
        $second = null;
        $nano = null;
        $ampm = null;
        $weekBasedYear = null;

        foreach ($this->captures as $capture) {
            $value = $matches[$capture->name] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            switch ($capture->symbol) {
                case 'Y':
                case 'X':
                    $year = (int) $value;

                    break;
                case 'o':
                    $weekBasedYear = (int) $value;

                    break;
                case 'y':
                    $twoDigitYear = (int) $value;

                    break;
                case 'm':
                case 'n':
                    $month = (int) $value;

                    break;
                case 'd':
                case 'j':
                    $day = (int) $value;

                    break;
                case 'H':
                case 'G':
                    $hour24 = (int) $value;

                    break;
                case 'h':
                case 'g':
                    $hour12 = (int) $value;

                    break;
                case 'i':
                    $minute = (int) $value;

                    break;
                case 's':
                    $second = (int) $value;

                    break;
                case 'f':
                    $digits = $value;
                    $length = \strlen($digits);
                    $length = $length > 9 ? 9 : $length;
                    $scaled = (int) ($digits . \str_repeat('0', 9 - $length));
                    if ($scaled !== 0) {
                        $nano = $scaled;
                    }

                    break;
                case 'u':
                    $micro = (int) $value;
                    if ($micro !== 0) {
                        $nano = $micro * 1_000;
                    }

                    break;
                case 'v':
                    $milli = (int) $value;
                    if ($milli !== 0) {
                        $nano = $milli * 1_000_000;
                    }

                    break;
                case 'a':
                case 'A':
                    $ampm = \strtolower($value);
                    break;
            }
        }

        if ($year === null && $twoDigitYear !== null) {
            $year = $twoDigitYear >= 70 ? $twoDigitYear + 1900 : $twoDigitYear + 2000;
        }

        if ($hour24 === null && $hour12 !== null) {
            $hour24 = $hour12 % 12;
            if ($ampm === 'pm') {
                $hour24 += 12;
            } elseif ($ampm === 'am' && $hour12 === 12) {
                $hour24 = 0;
            }
        }

        return new TemporalFields(
            year: $year,
            month: $month,
            day: $day,
            hour: $hour24,
            minute: $minute,
            second: $second,
            nano: $nano,
            weekBasedYear: $weekBasedYear,
        );
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function format(TemporalAccessor $accessor): string
    {
        return $this->formatTokens($this->tokens, $accessor);
    }

    private static function create(string $pattern): self
    {
        return new self($pattern, PatternCompiler::compile($pattern));
    }

    /**
     * @param list<Token> $tokens
     */
    private function formatTokens(array $tokens, TemporalAccessor $accessor): string
    {
        $output = '';

        foreach ($tokens as $token) {
            if ($token instanceof LiteralToken) {
                $output .= $token->value;
                continue;
            }

            if ($token instanceof FieldToken) {
                $output .= $this->formatField($accessor, $token);
                continue;
            }

            if ($token instanceof OptionalToken) {
                $segment = $this->formatTokens($token->tokens, $accessor);
                if ($segment !== '') {
                    $output .= $segment;
                }

                continue;
            }

            throw new \LogicException('Unknown token type encountered while formatting.');
        }

        return $output;
    }

    private function formatField(TemporalAccessor $accessor, FieldToken $token): string
    {
        $year = $accessor->get(TemporalField::Year);
        $month = $accessor->get(TemporalField::Month);
        $day = $accessor->get(TemporalField::Day);
        $hour24 = $accessor->get(TemporalField::Hour);
        $minute = $accessor->get(TemporalField::Minute);
        $second = $accessor->get(TemporalField::Second);
        $nano = $accessor->get(TemporalField::Nano);

        return match ($token->symbol) {
            'Y' => $this->formatFourDigitYear($year ?? 0, $token->length < 4 ? 4 : $token->length),
            'y' => \sprintf('%02d', ($year ?? 0) % 100),
            'X' => $this->formatExtendedYear($year ?? 0),
            'm' => \sprintf('%02d', $month ?? 0),
            'n' => (string) ($month ?? 0),
            'd' => \sprintf('%02d', $day ?? 0),
            'j' => (string) ($day ?? 0),
            'H' => \sprintf('%02d', $hour24 ?? 0),
            'G' => (string) ($hour24 ?? 0),
            'h' => \sprintf('%02d', $this->asClock12($hour24 ?? 0)),
            'g' => (string) $this->asClock12($hour24 ?? 0),
            'i' => \sprintf('%02d', $minute ?? 0),
            's' => \sprintf('%02d', $second ?? 0),
            'f' => $this->formatFraction($nano ?? 0, $token->length),
            'u' => \sprintf('%06d', (int) \floor(($nano ?? 0) / 1_000)),
            'v' => \sprintf('%03d', (int) \floor(($nano ?? 0) / 1_000_000)),
            'a' => ($hour24 ?? 0) >= 12 ? 'pm' : 'am',
            'A' => ($hour24 ?? 0) >= 12 ? 'PM' : 'AM',
            'z' => $this->formatDayOfYear($accessor),
            'W' => $this->formatIsoWeek($accessor),
            'o' => \sprintf('%04d', $accessor->get(TemporalField::WeekBasedYear) ?? 0),
            'w' => $this->formatWeekdayNumberSundayZero($accessor),
            'N' => $this->formatWeekdayNumberMondayOne($accessor),
            'L' => $year !== null && IsoCalendar::isLeapYear($year) ? '1' : '0',
            't' => $this->formatDaysInMonth($year, $month),
            'S' => $this->formatOrdinalSuffix($day ?? 0),
            'D' => $this->formatWeekdayName($accessor, false),
            'l' => $this->formatWeekdayName($accessor, true),
            'M' => $this->formatMonthName($month ?? 0, false),
            'F' => $this->formatMonthName($month ?? 0, true),
            default => '',
        };
    }

    private function formatFourDigitYear(int $year, int $width): string
    {
        $sign = $year < 0 ? '-' : '';
        $absYear = \abs($year);

        return $sign . \str_pad((string) $absYear, $width, '0', STR_PAD_LEFT);
    }

    private function formatExtendedYear(int $year): string
    {
        if ($year >= 10000 || $year <= -10000) {
            return \sprintf('%+d', $year);
        }

        return \sprintf('%04d', $year);
    }

    private function asClock12(int $hour): int
    {
        $clock = $hour % 12;

        return $clock === 0 ? 12 : $clock;
    }

    private function formatFraction(int $nano, int $length): string
    {
        if ($nano === 0) {
            return '';
        }

        $precision = $length > 9 ? 9 : $length;
        $padded = \sprintf('%09d', $nano);
        $digits = \rtrim(\substr($padded, 0, $precision), '0');
        if ($digits === '') {
            return '';
        }

        return '.' . $digits;
    }

    private function formatDayOfYear(TemporalAccessor $accessor): string
    {
        $dayOfYear = $accessor->get(TemporalField::DayOfYear);
        if ($dayOfYear === null) {
            return '0';
        }

        return (string) ($dayOfYear - 1);
    }

    private function formatIsoWeek(TemporalAccessor $accessor): string
    {
        $date = $this->extractLocalDate($accessor);
        if ($date === null) {
            return '00';
        }

        return \sprintf('%02d', $date->weekOfYear);
    }

    private function formatWeekdayNumberSundayZero(TemporalAccessor $accessor): string
    {
        $dayOfWeek = $accessor->get(TemporalField::DayOfWeek);
        if ($dayOfWeek === null) {
            return '0';
        }

        return (string) (($dayOfWeek + 1) % 7);
    }

    private function formatWeekdayNumberMondayOne(TemporalAccessor $accessor): string
    {
        $dayOfWeek = $accessor->get(TemporalField::DayOfWeek);
        if ($dayOfWeek === null) {
            return '1';
        }

        return (string) ($dayOfWeek + 1);
    }

    private function formatDaysInMonth(int|null $year, int|null $month): string
    {
        if ($year === null || $month === null) {
            return '0';
        }

        $hasThirtyOneDays = [1, 3, 5, 7, 8, 10, 12];
        if (\in_array($month, $hasThirtyOneDays, true)) {
            return '31';
        }

        if ($month === 2) {
            return IsoCalendar::isLeapYear($year) ? '29' : '28';
        }

        return '30';
    }

    private function formatOrdinalSuffix(int $day): string
    {
        if ($day <= 0) {
            return 'th';
        }

        $value = $day % 100;
        if ($value >= 11 && $value <= 13) {
            return 'th';
        }

        return match ($day % 10) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    private function formatWeekdayName(TemporalAccessor $accessor, bool $long): string
    {
        $dayOfWeek = $accessor->get(TemporalField::DayOfWeek);
        if ($dayOfWeek === null) {
            return '';
        }

        $index = $dayOfWeek >= 0 && $dayOfWeek <= 6 ? $dayOfWeek : 0;

        return $long ? Vocabulary::WEEKDAY_NAMES_FULL[$index] : Vocabulary::WEEKDAY_NAMES_SHORT[$index];
    }

    private function formatMonthName(int $month, bool $long): string
    {
        if ($month < 1 || $month > 12) {
            return '';
        }

        $index = $month - 1;

        return $long ? Vocabulary::MONTH_NAMES_FULL[$index] : Vocabulary::MONTH_NAMES_SHORT[$index];
    }

    private function extractLocalDate(TemporalAccessor $accessor): LocalDate|null
    {
        if ($accessor instanceof LocalDate) {
            return $accessor;
        }

        if ($accessor instanceof LocalDateTime) {
            return $accessor->date;
        }

        if ($accessor instanceof TemporalFields) {
            $year = $accessor->year;
            $month = $accessor->month;
            $day = $accessor->day;
            if ($year === null || $month === null || $day === null) {
                return null;
            }

            if ($month < 1 || $month > 12) {
                return null;
            }

            if ($day < 1 || $day > 31) {
                return null;
            }

            return LocalDate::of($year, $month, $day);
        }

        if ($accessor instanceof LocalTime) {
            return null;
        }

        return null;
    }
}
