# Design Philosophy

This document explains the design decisions and principles behind this date-time library.

## Core Principles

### 1. Immutability

All date-time types are immutable. Once created, their values cannot be changed.

**Why?**
- Thread-safe by default
- Prevents accidental modifications
- Enables safe sharing across components
- Simpler reasoning about code

```php
$date = LocalDate::of(2024, 3, 15);
$tomorrow = $date->plusDays(1); // Returns NEW instance

// $date is unchanged
echo $date; // "2024-03-15"
echo $tomorrow; // "2024-03-16"
```

### 2. Type Safety

Different concepts use different types to prevent mixing incompatible values.

**Types:**
- `LocalDate` - date without time
- `LocalTime` - time without date
- `LocalDateTime` - date and time without timezone
- `Instant` - UTC point in time
- `OffsetDateTime` - with fixed offset
- `ZonedDateTime` - with timezone (DST-aware)

```php
// Can't accidentally use a date where time is expected
function scheduleTask(LocalDateTime $when) { ... }

$date = LocalDate::now(); // Wrong type
$dateTime = LocalDateTime::now(); // Correct type

scheduleTask($dateTime); // ✓ OK
// scheduleTask($date); // ✗ Type error
```

### 3. Separation of Concerns

Date, time, and timezone concerns are separated into distinct types.

```php
// Date only (no time or timezone)
$birthday = LocalDate::of(1990, 5, 15);

// Time only (no date or timezone)
$alarmTime = LocalTime::of(7, 30);

// Date + time (no timezone)
$appointment = LocalDateTime::of(2024, 6, 15, 14, 30);

// Date + time + timezone
$meeting = ZonedDateTime::of(2024, 6, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
```

### 4. Explicit is Better Than Implicit

Operations and conversions are explicit to prevent confusion.

```php
// Explicit conversion
$instant = $zonedDateTime->toInstant();
$local = LocalDateTime::ofInstant($instant);

// Explicit timezone handling
$warsaw = ZonedDateTime::ofLocal($dateTime, ZoneId::of('Europe/Warsaw'));
$tokyo = $warsaw->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));
```

### 5. Fail Fast

Invalid operations throw exceptions immediately rather than producing incorrect results.

```php
try {
    $date = LocalDate::of(2024, 13, 1); // Invalid month
} catch (InvalidDate $e) {
    // Fails immediately with clear error
}

// Better than silently creating wrong date
```

## Inspired By

This library draws inspiration from:

1. **Java's `java.time`** (JSR-310)
   - Type separation (LocalDate, LocalDateTime, ZonedDateTime)
   - Immutability
   - Clear TempOral API

2. **C#'s NodaTime**
   - Strong typing
   - Separation of concerns
   - DST handling

3. **PHP's Native DateTime**
   - Interoperability
   - Timezone data from PHP

## Design Decisions

### Why Immutable?

```php
// Mutable (problematic):
$date->setDay(15); // Modifies original
$copy = $date; // $copy references same object
$copy->setDay(20); // Also changes $date!

// Immutable (safe):
$date = LocalDate::of(2024, 3, 15);
$modified = $date->withDay(20); // Returns new instance
// $date is unchanged, $modified is new object
```

### Why Separate Local and Zoned Types?

**Local types** (`LocalDate`, `LocalDateTime`) represent human-readable dates/times without timezone context:
- Calendar dates (birthdays, holidays)
- Scheduled times (meetings, appointments)
- Business logic dates

**Zoned types** (`ZonedDateTime`, `OffsetDateTime`) represent actual points in time:
- Event timestamps
- Cross-timezone coordination
- Historical records

```php
// Birthday is a local date (same for everyone)
$birthday = LocalDate::of(1990, 5, 15);

// Event timestamp needs timezone
$event = ZonedDateTime::of(2024, 6, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
```

### Why Both ZonedDateTime and OffsetDateTime?

**OffsetDateTime** - Fixed offset, simpler:
- ISO-8601 timestamps
- APIs with fixed offsets
- No DST complexity

**ZonedDateTime** - Full timezone support:
- User-facing times
- DST transitions
- Future dates (offset may change)

```php
// API timestamp: fixed offset
$api = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, Offset::of(2, 0));

// User event: timezone-aware
$user = ZonedDateTime::of(2024, 7, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
// Automatically handles DST change
```

### Why Period and Duration?

**Period** - Calendar-based (variable):
- 1 month (28-31 days)
- 1 year (365-366 days)

**Duration** - Time-based (exact):
- 2 hours (always 7200 seconds)
- 30 minutes (always 1800 seconds)

```php
// Period: varies by month
$date1 = LocalDate::of(2024, 1, 31)->plusPeriod(new Period(months: 1));
// Result: 2024-02-29 (Feb has 29 days in 2024)

$date2 = LocalDate::of(2024, 2, 29)->plusPeriod(new Period(months: 1));
// Result: 2024-03-29 (March has 31 days)

// Duration: always exact
$time = LocalDateTime::of(2024, 3, 31, 1, 30); // Before DST
$later = $time->plus(new Duration(hours: 24));
// Exactly 24 hours later (may span DST transition)
```

## API Design Patterns

### Factory Methods

Static factory methods instead of multiple constructors:

```php
// ✓ Clear intent
$date = LocalDate::of(2024, 3, 15);
$today = LocalDate::now();
$fromEpoch = LocalDate::fromEpochDay(19797);

// ✗ Would be confusing with constructors
// new LocalDate(2024, 3, 15);
// new LocalDate(time());
```

### Fluent Interface

Method chaining for readability:

```php
$date = LocalDate::of(2024, 1, 15)
    ->plusMonths(2)
    ->withDay(1)
    ->plusYears(1);
// Result: 2025-03-01
```

### Named Parameters

Use for clarity:

```php
// Clear and flexible
$duration = new Duration(hours: 2, minutes: 30);
$period = new Period(years: 1, months: 6);

// Can skip middle parameters
$time = LocalTime::of(hour: 14, minute: 30);
```

## Error Handling Philosophy

### Specific Exceptions

Each error type has its own exception for precise handling:

```php
try {
    $object = createFromUserInput($data);
} catch (InvalidDate $e) {
    // Handle date errors
} catch (InvalidTime $e) {
    // Handle time errors
} catch (InvalidZoneId $e) {
    // Handle timezone errors
}
```

### Fail Fast, Fail Loudly

Don't silently fix or ignore errors:

```php
// ✓ Throws exception
LocalDate::of(2024, 2, 30); // InvalidDate

// ✗ Would be wrong
// LocalDate::of(2024, 2, 30); // Silently converts to Feb 28/29
```

## Performance Considerations

### Caching

Commonly used offsets are cached:

```php
// Cached offsets (faster)
$utc = Offset::utc();
$common = Offset::of(2, 0); // 15-min increments cached

// Not cached (still fast, but allocates)
$uncommon = Offset::ofTotalSeconds(7261);
```

### Lazy Loading

Timezone rules are loaded on demand:

```php
$zone = ZoneId::of('Europe/Warsaw'); // Fast, doesn't load rules
$rules = $zone->getRules(); // Loads rules when needed
```

## Future Compatibility

### ISO-8601 Standard

All formatting follows ISO-8601:

```php
echo LocalDate::of(2024, 3, 15); // "2024-03-15"
echo LocalTime::of(14, 30); // "14:30:00"
echo Duration::parse('PT2H30M'); // Standard format
```

### Extensibility

Temporal system allows custom fields and adjusters:

```php
use Brzuchal\DateTime\Temporal\TemporalAdjuster;

$date = LocalDate::now();
$adjusted = $date->with(TemporalAdjuster::firstDayOfMonth());
```

## Best Practices Summary

1. **Choose the Right Type**
   - Local for human dates/times
   - Instant for timestamps
   - Zoned for timezone-aware times

2. **Store Wisely**
   - Database: Use `Instant` (UTC)
   - Display: Convert to `ZonedDateTime`

3. **Handle Errors**
   - Validate user input
   - Catch specific exceptions
   - Provide helpful messages

4. **Be Explicit**
   - Timezone conversions
   - Format specifications
   - Component access

5. **Leverage Immutability**
   - Share freely
   - Chain operations
   - Reason locally

## See Also

- [Getting Started](getting-started.md) - Basic usage
- [Exceptions](exceptions.md) - Error handling
