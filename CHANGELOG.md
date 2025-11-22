# Changelog

## [1.0.0] - 2025-11-22

### Breaking Changes
- **Duration Refactoring:**
  - `Duration` is now strictly time-based (hours, minutes, seconds, nanos).
  - Removed `years`, `months`, `days` properties and factory methods from `Duration`.
  - Removed `LocalDate::plusDuration()` and `LocalDate::minusDuration()`. Use `plusPeriod()` instead.

### New Features
- **Period:**
  - Introduced `Period` class for date-based amounts (years, months, days).
  - Added `LocalDate::plusPeriod()` and `LocalDate::minusPeriod()`.
  - Added `LocalDateTime::plusPeriod()` and `LocalDateTime::minusPeriod()`.
- **LocalDateTimeDelta:**
  - Introduced `LocalDateTimeDelta` value object combining `Period` and `Duration`.
  - Supports full ISO-8601 duration parsing (e.g., `P1Y2M3DT4H5M6S`).
  - Added `LocalDateTime::plusDelta()` and `LocalDateTime::minusDelta()`.
- **ISO-8601 Parsing:**
  - Centralized ISO-8601 parsing logic in `Iso8601Parser`.
  - Unified parsing behavior across `Period`, `Duration`, and `LocalDateTimeDelta`.

### API Additions
  - Introduce a LocalTime value object with validation, arithmetic helpers, and TemporalAccessor integration.
  - Add InvalidTime exception for clearer range violations.
  - Add LocalDateTime aggregate covering combined date/time arithmetic, Instant conversion, and TemporalAccessor support.
- **Security**
  - Harden `LocalDate::__unserialize()` to validate payload structure, numeric fields, and calendar membership before instantiation.
  - Add regression tests covering malicious serialized input to prevent assertion bypasses and fatal errors.
- **LocalDate duration arithmetic**
  - Respect hours/minutes/seconds/nanos when adding or subtracting durations from LocalDate.
  - Introduce day-level conversion of Duration time components so sub-day offsets roll dates correctly.
  - Expand the LocalDate test suite with BCE transitions and time-based duration cases.
- **ISO duration parsing**
  - Reject fractional years/months/days/hours/minutes in ISO standard duration parsing instead of truncating.
  - Support fractional seconds in ISO extended duration parsing/formatting with nanosecond precision.
  - Add regression tests covering fractional inputs for both duration formats.
- **Calendar adjustments**
  - Replace the runtime calendar registry with a single static `IsoCalendar` helper that owns all ISO conversions.
  - Keep ISO arithmetic ISO-only via `IsoCalendar`; future calendars can introduce dedicated helpers without shared registries.
  - Skip astronomical year zero when shifting months across BCE/CE boundaries.
  - Normalize ISO week-based arithmetic for BCE years to avoid invalid modulo results.
  - Normalize IsoCalendar 400-year cycle arithmetic so yearStartDay() remains accurate for negative years.
  - Fix LocalDate weekOfYear/weekOfMonth calculations to follow ISO rules and add boundary tests.
- **Documentation**
  - Refresh README with ISO-only messaging, `isoEra` examples, and internal overview.
  - Clarify review checklist to reference the new static `IsoCalendar` architecture.
- **Dependency clean-up**
  - Remove unused ext-gmp requirement from composer metadata and docs.
- **Temporal units**
  - Centralise nano/second/minute/hour/day constants in `TimeUnit` and reuse across LocalTime, LocalDateTime, and Duration.
  - Expose `Duration::toTotalDays()` and reuse it in LocalDate to route time-derived day spillover.
  - Normalize docblock code snippets to `<code>` blocks for IDE-friendly tooltips.
