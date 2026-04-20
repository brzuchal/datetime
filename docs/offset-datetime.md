# OffsetDateTime

`OffsetDateTime` represents a date-time with a fixed UTC offset, without timezone or DST awareness.

## Overview

Use `OffsetDateTime` when you need a fixed offset from UTC without the complexity of timezone rules. The offset never changes, making it simpler than `ZonedDateTime` but less flexible.

```php
use Brzuchal\DateTime\OffsetDateTime;
use Brzuchal\DateTime\Offset;

$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, Offset::of(2, 0));
echo $odt; // "2024-03-15T14:30:00+02:00"
```

## When to Use

- **ISO-8601 timestamps**: APIs that work with offset-based timestamps
- **Fixed offset storage**: When you don't need DST handling
- **Simple timezone scenarios**: Single-offset systems
- **Intermediate conversion**: Converting between `LocalDateTime` and `Instant`

## Creating OffsetDateTime

### From Components

```php
use Brzuchal\DateTime\OffsetDateTime;
use Brzuchal\DateTime\Offset;

$offset = Offset::of(2, 0); // +02:00

// Create with offset
$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, $offset);

// Current date-time with offset
$now = OffsetDateTime::now($offset);

// Current date-time with system timezone offset
$now = OffsetDateTime::now();
```

### From LocalDateTime

```php
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\Offset;

$dateTime = LocalDateTime::of(2024, 3, 15, 14, 30);
$offset = Offset::of(2, 0);

$odt = OffsetDateTime::ofDateTimeAndOffset($dateTime, $offset);
```

### From Instant

```php
use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\Offset;

$instant = Instant::now();
$offset = Offset::of(2, 0);

$odt = OffsetDateTime::ofInstant($instant, $offset);
```

## Accessing Components

```php
$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, Offset::of(2, 0));

// Access local date-time
$dateTime = $odt->dateTime;

// Access offset
$offset = $odt->offset;
echo $offset->totalSeconds; // 7200 (2 hours)
```

## Changing Offset

### Same Instant, Different Offset

Changes the offset while keeping the same point in time (UTC):

```php
$odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, Offset::of(2, 0));
// 2024-03-15T14:00:00+02:00

$odt2 = $odt1->withOffsetSameInstant(Offset::of(5, 0));
// 2024-03-15T17:00:00+05:00
// Same UTC time, different local time
```

### Same Local Time, Different Offset

Changes the offset while keeping the same local date-time:

```php
$odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, Offset::of(2, 0));
// 2024-03-15T14:00:00+02:00

$odt2 = $odt1->withOffsetSameLocal(Offset::of(5, 0));
// 2024-03-15T14:00:00+05:00
// Same local time, different UTC time
```

## Converting to Other Types

```php
$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, Offset::of(2, 0));

// Convert to UTC instant
$instant = $odt->toInstant();

// Get UTC epoch second (deprecated, use toInstant() instead)
$epochSecond = $odt->toEpochSecond();
```

## Comparing

```php
$odt1 = OffsetDateTime::of(2024, 3, 15, 14, 0, 0, 0, Offset::of(2, 0));
$odt2 = OffsetDateTime::of(2024, 3, 15, 17, 0, 0, 0, Offset::of(5, 0));

// Compare instants (regardless of offset)
if ($odt1->equals($odt2)) {
    echo "Same point in time"; // true - both represent same UTC time
}

if ($odt1->isBefore($odt2)) {
    echo "odt1 is before odt2";
}

if ($odt2->isAfter($odt1)) {
    echo "odt2 is after odt1";
}
```

## Formatting

```php
$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 45, 123456789, Offset::of(2, 30));

// ISO-8601 format
echo $odt; // "2024-03-15T14:30:45.123456789+02:30"

// Without nanoseconds
$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, Offset::of(2, 0));
echo $odt; // "2024-03-15T14:30:00+02:00"
```

## Examples

### API Timestamp

```php
// Store API response timestamp
$response = [
    'timestamp' => '2024-03-15T14:30:00+02:00',
    'data' => [/* ... */]
];

// Parse timestamp
$odt = OffsetDateTime::parse($response['timestamp']);

// Convert to UTC for storage
$instant = $odt->toInstant();
```

### Fixed Offset Scheduling

```php
// Event at a specific time in a specific offset
$event = OffsetDateTime::of(2024, 12, 25, 19, 0, 0, 0, Offset::of(2, 0));

// Check if event has passed
$now = OffsetDateTime::now(Offset::of(2, 0));
if ($now->isAfter($event)) {
    echo "Event has passed";
}
```

### Offset-based Logging

```php
function logWithOffset(string $message, Offset $offset): void
{
    $timestamp = OffsetDateTime::now($offset);
    echo "[{$timestamp}] {$message}\n";
}

$serverOffset = Offset::of(2, 0);
logWithOffset("Server started", $serverOffset);
// [2024-03-15T14:30:00+02:00] Server started
```

## OffsetDateTime vs ZonedDateTime

| Feature | OffsetDateTime | ZonedDateTime |
|---------|---------------|---------------|
| Offset | Fixed | Can change (DST) |
| Timezone rules | No | Yes |
| DST handling | No | Yes |
| Complexity | Lower | Higher |
| Use case | Fixed offset APIs | User-facing times |
| Storage | Simpler | Requires timezone data |

### Example Difference

```php
// OffsetDateTime - offset never changes
$odt1 = OffsetDateTime::of(2024, 1, 15, 12, 0, 0, 0, Offset::of(1, 0));
$odt2 = OffsetDateTime::of(2024, 7, 15, 12, 0, 0, 0, Offset::of(1, 0));
echo $odt1->offset; // +01:00
echo $odt2->offset; // +01:00 (same)

// ZonedDateTime - offset changes with DST
$zdt1 = ZonedDateTime::of(2024, 1, 15, 12, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
$zdt2 = ZonedDateTime::of(2024, 7, 15, 12, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
echo $zdt1->offset; // +01:00 (winter)
echo $zdt2->offset; // +02:00 (summer, DST)
```

## Best Practices

1. **Use for APIs**: Ideal for ISO-8601 API timestamps
2. **Avoid for user times**: Use `ZonedDateTime` for user-facing times
3. **Database storage**: Consider storing as `Instant` (UTC) instead
4. **Conversion**: Easy to convert to/from `Instant` and `LocalDateTime`
5. **No DST logic**: Perfect when you don't want timezone complexity

## Common Patterns

### UTC-based APIs

```php
// Receive UTC timestamp from API
$utcOffset = Offset::utc();
$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, $utcOffset);

// Store as instant
$instant = $odt->toInstant();

// Display in user's offset
$userOffset = Offset::of(2, 0);
$userTime = OffsetDateTime::ofInstant($instant, $userOffset);
```

### Converting from ZonedDateTime

```php
// When you need a fixed offset representation
$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
$odt = $zdt->toOffsetDateTime();
// Loses timezone info, keeps current offset
```

## See Also

- [Offset](offset.md) - UTC offset representation
- [ZonedDateTime](zoned-datetime.md) - Timezone-aware alternative
- [Instant](instant.md) - UTC point in time
- [LocalDateTime](local-datetime.md) - Without offset
