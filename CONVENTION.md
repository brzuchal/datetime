# Project Conventions and Coding Standards

This document outlines the coding standards, naming conventions, and design principles used in this project. It draws inspiration from top-tier languages and libraries (Java's `java.time`, C#'s NodaTime, Python's `datetime`, Rust's `chrono`) to ensure a robust and familiar developer experience.

## 1. Naming Conventions

### General Rules
- **Classes & Interfaces**: `PascalCase` (e.g., `LocalDateTime`, `ZoneId`).
- **Methods**: `camelCase` (e.g., `plusDays`, `withZoneSameInstant`).
- **Variables**: `camelCase` (e.g., `dateTime`, `zoneRules`).
- **Constants**: `UPPER_SNAKE_CASE` (e.g., `MIN_SECONDS`, `ISO_8601`).

### Date-Time Domain Naming
We follow a specific taxonomy to distinguish between different time concepts, avoiding ambiguity common in older libraries.

| Concept | Class Name | Rationale & Inspiration |
|---------|------------|-------------------------|
| **Date only** | `LocalDate` | Standard in Java (`java.time.LocalDate`) and NodaTime. Clearer than just `Date`. |
| **Time only** | `LocalTime` | Standard in Java and NodaTime. |
| **Date + Time** | `LocalDateTime` | "Local" implies lack of timezone context. Standard in Java. |
| **Zoned Time** | `ZonedDateTime` | Explicitly states it has a timezone. Standard in Java. |
| **Offset Time** | `OffsetDateTime` | Explicitly states it has a fixed offset. Standard in Java. |
| **Machine Time** | `Instant` | Represents a point on the timeline. Standard in Java and NodaTime. |

### The "Delta" vs "Diff" Convention
One of the key naming decisions in this library is `DateTimeDelta`.

*   **`Period`**: Represents a date-based amount (years, months, days).
*   **`Duration`**: Represents a time-based amount (hours, minutes, seconds, nanos).
*   **`DateTimeDelta`**: Represents a combined amount of time (Period + Duration).

**Why "Delta"?**
*   **Noun vs Verb**: "Diff" is often interpreted as a verb (operation) or the result of a subtraction. "Delta" is a standard mathematical/scientific term for "change" or "difference" as a noun/object.
*   **Cross-Language Precedent**:
    *   **Python**: Uses `timedelta` for the difference between two dates/times.
    *   **Rust (chrono)**: Uses `TimeDelta` (alias for Duration) for differences.
    *   **Physics/Math**: $\Delta t$ is the universal symbol for a time interval or change.
*   **Clarity**: `DateTimeDiff` sounds like a utility class that *performs* the difference. `DateTimeDelta` sounds like a value object *representing* the difference.

**Why not "Interval" or "Span"?**
*   **Interval**: Often implies two fixed points on a timeline (Start + End), rather than an amount of time (e.g., "2 days"). (See Java's `Interval` in ThreeTen-Extra or Joda-Time).
*   **Span**: Used in some libraries (Rust's Jiff), but "Delta" is more widely recognized for "difference".

## 2. Method Naming Patterns

We use consistent prefixes to make the API discoverable (fluent interface style).

| Prefix | Usage | Example |
|--------|-------|---------|
| `of` | Static factory method, usually validating input. | `LocalDate::of(2024, 1, 1)` |
| `from` | Static factory converting from another type. | `LocalDateTime::from(other)` |
| `parse` | Static factory parsing a string. | `Duration::parse("PT2H")` |
| `get` | Accessor for a property (or use public readonly properties). | `getRules()` |
| `is` | Boolean check. | `isLeapYear()`, `isBefore()` |
| `with` | Immutable "setter" - returns a copy with changed value. | `withYear(2025)` |
| `plus` | Returns a copy with amount added. | `plusDays(5)` |
| `minus` | Returns a copy with amount subtracted. | `minusHours(2)` |
| `to` | Converts to another type. | `toInstant()`, `toString()` |
| `at` | Combines with another type. | `date->atTime(time)` |

## 3. Coding Style (PHP)

*   **Standard**: We adhere strictly to **PSR-12** coding standards.
*   **Types**: Strict typing (`declare(strict_types=1);`) is mandatory in all files.
*   **Immutability**: All date-time value objects must be immutable. Any modification method must return a new instance (`return new self(...)` or `clone $this`).
*   **Visibility**: Properties should be `public readonly` where possible to avoid boilerplate getters, unless validation logic is required on access (rare for value objects).
*   **Final**: Value object classes should be `final` to prevent inheritance abuse, promoting composition instead.

## 4. Testing Conventions

*   **Framework**: PHPUnit.
*   **Naming**: `testMethodNameCondition` or `test_method_name_condition`.
*   **Coverage**: Aim for high coverage of domain logic, especially edge cases (leap years, DST transitions).
*   **Assertions**: Use specific assertions (`assertSame` over `assertEquals` for strict type checking).

## 5. Exception Handling

*   **Specific Exceptions**: Throw specific exceptions (`InvalidDate`, `InvalidOffset`) rather than generic `Exception` or `InvalidArgumentException`.
*   **Hierarchy**: All library exceptions extend a common marker interface or base class if possible (currently extending `\Exception`).
*   **Fail Fast**: Validate inputs immediately in constructors or factory methods.

---
*This document serves as a guide for contributors to maintain consistency and quality across the codebase.*
