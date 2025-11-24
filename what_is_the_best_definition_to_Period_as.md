### Summary
You already have `Duration` that mixes date and time components. Adding a dedicated `Period` model can clarify semantics and cover calendar math that is not well captured by time-based durations.

- `Period` = calendar-based amount (years, months, days). Not an exact number of seconds. Use it to move along the calendar on `LocalDate`/`LocalDateTime` (e.g., “add 3 months”).
- `Duration` = time-based amount (hours, minutes, seconds, nanos) representing a precise number of seconds. Use it for clock time and `Instant` arithmetic (e.g., “add 90 minutes” = 5400 s).

This separation mirrors the design in Java Time (`java.time.Period` vs `java.time.Duration`) and fills gaps around month/year arithmetic, end-of-month handling, and operations between two dates.

---

### Why add `Period` if `Duration` already has years/months/days?
Your current `Duration` includes `years`, `months`, `days`, `hours`, `minutes`, `seconds`, `nanos`. While convenient, it blurs two different concepts:

- Months/years vary in length; they cannot be mapped to a fixed number of seconds without a reference date.
- `Duration` should be safely comparable and addable to an `Instant` because it’s exact; calendar components make that ambiguous.

By introducing a `Period`:
- Calendar math (Y-M-D) lives in `Period`.
- Exact time math (H-M-S-nanos) lives in `Duration`.
- `LocalDate.plus(...)` prefers `Period`, `Instant.plus(...)` prefers `Duration`.

This gives strong typing, fewer footguns, and clearer APIs.

---

### Proposed `Period` responsibilities
- Represent a calendar amount with fields: `years`, `months`, `days` (integers; allow negative).
- Be immutable (`final readonly`).
- Provide ISO‑8601 parsing/formatting for date-part periods: `P[nY][nM][nD]` (no `T` section).
- Support arithmetic: `plus`, `minus`, `negated`, `multipliedBy`, `normalized`.
- Provide factories: `ofYears`, `ofMonths`, `ofWeeks`, `ofDays`, `parse`.
- Provide queries/transforms: `isZero`, `isNegative`, `toTotalMonths`.
- Interoperate with other types:
  - `LocalDate.plus(Period)` and `LocalDate.minus(Period)`.
  - `LocalDateTime.plus(Period)` and `LocalDateTime.minus(Period)` on the date part only.
  - Conversions that require a reference date: `toDuration(LocalDate $anchor)` (optional, careful with semantics), or better: `LocalDate.until(LocalDate $end): Period`.

---

### Edge-case policy (crucial for correctness)
- End-of-month handling: Adding months/years should preserve the day-of-month when possible; if not possible, clamp to the last valid day of the target month. Example: `2025-01-31 + Period::ofMonths(1) = 2025-02-28` (or 29 in leap-year), aligning with typical `plusMonths` semantics.
- Negative values: `Period(-1, -2, -3)` is valid. Define `normalized` to collapse months to years where possible: e.g. `(0, 14, 0).normalized() -> (1, 2, 0)`. Keep days separate; do not auto-convert days to months/years because month/day length varies.
- Comparability: Avoid total ordering (`compareTo`) since a `Period(1 month)` may be `28..31` days. Provide `equals` based on structural equality of components.

---

### Suggested PHP API sketch

```php
<?php declare(strict_types=1);

namespace Brzuchal\DateTime;

use Brzuchal\DateTime\Format\PeriodFormat;        // analogous to DurationFormat
use Brzuchal\DateTime\Format\PeriodFormatter;     // analogous to DurationFormatter

final readonly class Period
{
    public function __construct(
        public int $years = 0,
        public int $months = 0,
        public int $days = 0,
    ) {}

    // Factories
    public static function of(int $years = 0, int $months = 0, int $days = 0): self
    { return new self($years, $months, $days); }

    public static function ofYears(int $years): self { return new self($years, 0, 0); }
    public static function ofMonths(int $months): self { return new self(0, $months, 0); }
    public static function ofWeeks(int $weeks): self { return new self(0, 0, $weeks * 7); }
    public static function ofDays(int $days): self { return new self(0, 0, $days); }

    // Parsing/formatting (ISO-8601 date-based: PnYnMnD; no time section)
    public static function parse(string $text, PeriodFormat $format = PeriodFormat::IsoStandard): self
    { return PeriodFormatter::of($format)->parse($text); }

    public function format(PeriodFormat $format = PeriodFormat::IsoStandard): string
    { return PeriodFormatter::of($format)->format($this); }

    public function __toString(): string { return $this->format(); }

    // Queries
    public function isZero(): bool { return $this->years === 0 && $this->months === 0 && $this->days === 0; }
    public function isNegative(): bool { return $this->years < 0 || $this->months < 0 || $this->days < 0; }
    public function toTotalMonths(): int { return ($this->years * 12) + $this->months; }

    // Arithmetic
    public function plus(int $years = 0, int $months = 0, int $days = 0): self
    { return new self($this->years + $years, $this->months + $months, $this->days + $days); }

    public function minus(int $years = 0, int $months = 0, int $days = 0): self
    { return new self($this->years - $years, $this->months - $months, $this->days - $days); }

    public function negated(): self
    { return new self(-$this->years, -$this->months, -$this->days); }

    public function multipliedBy(int $scalar): self
    { return new self($this->years * $scalar, $this->months * $scalar, $this->days * $scalar); }

    // Normalization: collapse months >= 12 to years. Days remain unchanged.
    public function normalized(): self
    {
        $y = $this->years; $m = $this->months; $d = $this->days;
        $y += intdiv($m, 12);
        $m = $m % 12;
        // keep days as-is; no conversion to months/years
        return new self($y, $m, $d);
    }
}
```

If you want to keep `Duration` backward-compatible, leave it as-is for now, but document that `years/months/days` on `Duration` are deprecated in favor of `Period`. Over time, steer users toward:
- `Duration` for `Instant`/`LocalTime` operations.
- `Period` for `LocalDate`/`LocalDateTime` date-part operations.

---

### Interoperability design
- `LocalDate`:
  - `plus(Period $p): LocalDate`
  - `minus(Period $p): LocalDate`
  - `until(LocalDate $end): Period` (date-based difference, e.g., `2025-01-15` until `2026-03-20` -> `P1Y2M5D` with well-defined carry rules)

- `LocalDateTime`:
  - `plus(Period $p): LocalDateTime` (affects only the date part)
  - For time adjustments use `plus(Duration $d)`

- `Instant`:
  - Only accepts `Duration`, not `Period`. If needed, provide a helper that converts `Period` to `Duration` with an anchor date and zone, but prefer placing that on `LocalDate`/`ZonedDateTime` rather than `Period` itself:
    - `ZonedDateTime.plus(Period $p)`: derives the correct instant based on calendar rules and DST transitions.

---

### Parsing/formatting considerations
- `Period` ISO-only parser should reject a `T` time section. Example accepted: `P2Y3M10D`, `P-1Y`, `P15D`, `P0D`. Rejected: `PT36H`, `P1YT2H`.
- If you want a single formatter hierarchy, consider a `TemporalAmountFormatter` with two modes, or keep separate `DurationFormatter`/`PeriodFormatter` for clarity.

---

### Migration/compat strategy for current `Duration`
- Mark `Duration::$years`, `$months`, `$days` as deprecated in PHPDoc, guide users to `Period`.
- Add convenience bridges:
  - `Period::fromDuration(Duration $d): Period` that picks only Y-M-D components (document that time fields are discarded).
  - `Duration::timeOnly(Duration $d): Duration` to zero-out Y-M-D components.
- In documentation and tests, prefer examples using `Period` for months/years.

---

### Tests to add (illustrative)
- Parsing/formatting round-trips for `P1Y2M3D`, `P-3M`, `P0D`.
- Arithmetic: `Period::ofMonths(14)->normalized()` -> `P1Y2M`.
- `LocalDate.plus(Period::ofMonths(1))` end-of-month: `2024-01-31` -> `2024-02-29`.
- `LocalDate.until($end)` scenarios with cross-year and negative results.
- Ensure `Instant` rejects `Period` and accepts `Duration`.

---

### Resulting roles in the codebase
- `Period` fills the gap of human-calendar adjustment and date-based differences.
- `Duration` remains the precise, time-based amount for `Instant` and clock math.
- Together, they make APIs intention-revealing and safer: users pick the right abstraction for their problem.

If you’d like, I can tailor the `Period` end-of-month rule or the `until` carry strategy to match your existing `LocalDate` semantics based on your current codebase.