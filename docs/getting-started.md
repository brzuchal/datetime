# Getting Started

## Overview

This library provides a comprehensive set of immutable date-time types for PHP, designed to handle various date-time scenarios from simple dates to complex timezone-aware operations.

## Basic Concepts

### Immutability

All date-time objects in this library are **immutable**. This means that operations like `plus()`, `minus()`, or `with()` return new instances rather than modifying the original:

```php
use Brzuchal\DateTime\LocalDate;

$date = LocalDate::of(2024, 3, 15);
$nextDay = $date->plusDays(1); // Returns new instance

echo $date;     // "2024-03-15" (unchanged)
echo $nextDay;  // "2024-03-16"
```

### Type Hierarchy

The library provides three categories of date-time types:

1. **Local Types** (no timezone information)
   - `LocalDate` - date only
   - `LocalTime` - time only
   - `LocalDateTime` - date and time

2. **Timezone-Aware Types**
   - `Offset` - UTC offset (e.g., +02:00)
   - `OffsetDateTime` - date-time with fixed offset
   - `ZoneId` - timezone identifier
   - `ZonedDateTime` - date-time with full timezone support

3. **Temporal Amounts**
   - `Duration` - time-based amount (hours, minutes, seconds)
   - `Period` - date-based amount (years, months, days)
   - `DateTimeDelta` - combination of period and duration

## Common Operations

### Creating Date-Time Objects

```php
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\LocalDateTime;

// Create from components
$date = LocalDate::of(2024, 3, 15);
$time = LocalTime::of(14, 30, 0);
$dateTime = LocalDateTime::of(2024, 3, 15, 14, 30);

// Get current values
$now = LocalDateTime::now();
$today = LocalDate::now();
```

### Modifying Date-Time Objects

```php
use Brzuchal\DateTime\LocalDate;

$date = LocalDate::of(2024, 3, 15);

// Add/subtract values
$future = $date->plusDays(7);
$past = $date->minusMonths(2);

// Set specific components
$modified = $date->withMonth(12)->withDay(25);
```

### Comparing Date-Time Objects

```php
use Brzuchal\DateTime\LocalDateTime;

$dt1 = LocalDateTime::of(2024, 3, 15, 10, 0);
$dt2 = LocalDateTime::of(2024, 3, 15, 14, 0);

if ($dt1->isBefore($dt2)) {
    echo "dt1 is before dt2";
}

$comparison = $dt1->compareTo($dt2); // -1, 0, or 1
```

### Formatting and Parsing

```php
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\Format\DateTimeFormat;

// Format to string
$date = LocalDate::of(2024, 3, 15);
echo $date; // "2024-03-15" (ISO-8601)

// Parse from string
$formatter = DateTimeFormat::ofPattern('yyyy-MM-dd');
$parsed = LocalDate::parse('2024-03-15', $formatter);
```

## Working with Timezones

### Fixed Offset

Use `OffsetDateTime` when you need a fixed UTC offset without DST awareness:

```php
use Brzuchal\DateTime\OffsetDateTime;
use Brzuchal\DateTime\Offset;

$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, Offset::of(2, 0));
echo $odt; // "2024-03-15T14:30:00+02:00"
```

### Timezone with DST

Use `ZonedDateTime` for full timezone support including DST transitions:

```php
use Brzuchal\DateTime\ZonedDateTime;
use Brzuchal\DateTime\ZoneId;

$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
echo $zdt; // "2024-03-15T14:30:00+01:00[Europe/Warsaw]"

// Convert to different timezone
$tokyo = $zdt->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));
```

## Next Steps

- Learn about [LocalDate](local-date.md) for working with dates
- Learn about [LocalDateTime](local-datetime.md) for date-time operations
- Learn about [ZonedDateTime](zoned-datetime.md) for timezone-aware operations
- Learn about [Duration and Period](duration.md) for temporal amounts
