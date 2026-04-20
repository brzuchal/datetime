# ZonedDateTime

`ZonedDateTime` represents a date-time with full timezone support, including daylight saving time (DST) transitions.

## Overview

Unlike `OffsetDateTime` which has a fixed offset, `ZonedDateTime` is aware of timezone rules and automatically handles DST transitions.

```php
use Brzuchal\DateTime\ZonedDateTime;
use Brzuchal\DateTime\ZoneId;

$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));
echo $zdt; // "2024-03-15T14:30:00+01:00[Europe/Warsaw]"
```

## Creating ZonedDateTime

### From Components

```php
use Brzuchal\DateTime\ZonedDateTime;
use Brzuchal\DateTime\ZoneId;

$zone = ZoneId::of('Europe/Warsaw');

// Create with timezone
$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, $zone);

// With seconds and nanoseconds
$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 45, 123456789, $zone);

// Current date-time in timezone
$now = ZonedDateTime::now($zone);

// Current date-time in system default timezone
$now = ZonedDateTime::now();
```

### From LocalDateTime

```php
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\ZoneId;

$dateTime = LocalDateTime::of(2024, 3, 15, 14, 30);
$zone = ZoneId::of('Europe/Warsaw');

$zdt = ZonedDateTime::ofLocal($dateTime, $zone);
```

### From Instant

```php
use Brzuchal\DateTime\Instant;
use Brzuchal\DateTime\ZoneId;

$instant = Instant::now();
$zone = ZoneId::of('Europe/Warsaw');

$zdt = ZonedDateTime::ofInstant($instant, $zone);
```

## Accessing Components

```php
$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));

// Access local date-time
$dateTime = $zdt->dateTime;

// Access timezone
$zone = $zdt->zone;

// Access current offset (may change with DST)
$offset = $zdt->offset;
```

## Timezone Conversion

### Same Instant, Different Timezone

Converts to a different timezone while keeping the same point in time:

```php
$warsaw = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
// 2024-03-15T14:00:00+01:00[Europe/Warsaw]

$tokyo = $warsaw->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));
// 2024-03-15T22:00:00+09:00[Asia/Tokyo]
// Same UTC time, different local time
```

### Same Local Time, Different Timezone

Changes timezone while keeping the same local date-time:

```php
$warsaw = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
// 2024-03-15T14:00:00+01:00[Europe/Warsaw]

$tokyo = $warsaw->withZoneSameLocal(ZoneId::of('Asia/Tokyo'));
// 2024-03-15T14:00:00+09:00[Asia/Tokyo]
// Same local time, different UTC time
```

## DST Handling

### Gap Resolution

During "spring forward" DST transitions, some local times don't exist:

```php
// In Europe/Warsaw, 2024-03-31 02:00 jumps to 03:00
$zone = ZoneId::of('Europe/Warsaw');

// This local time doesn't exist
$zdt = ZonedDateTime::of(2024, 3, 31, 2, 30, 0, 0, $zone);
// Automatically adjusts forward: 2024-03-31T03:30:00+02:00[Europe/Warsaw]
```

### Overlap Resolution

During "fall back" DST transitions, some local times occur twice:

```php
// In Europe/Warsaw, 2024-10-27 03:00 falls back to 02:00
$zone = ZoneId::of('Europe/Warsaw');

// This local time occurs twice
$zdt = ZonedDateTime::of(2024, 10, 27, 2, 30, 0, 0, $zone);
// Prefers earlier offset (DST): 2024-10-27T02:30:00+02:00[Europe/Warsaw]
```

## Converting to Other Types

```php
$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));

// Convert to UTC instant
$instant = $zdt->toInstant();

// Convert to OffsetDateTime (loses timezone info)
$odt = $zdt->toOffsetDateTime();
```

## Comparing

```php
$warsaw1 = ZonedDateTime::of(2024, 3, 15, 14, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
$warsaw2 = ZonedDateTime::of(2024, 3, 15, 16, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
$tokyo = ZonedDateTime::of(2024, 3, 15, 22, 0, 0, 0, ZoneId::of('Asia/Tokyo'));

// Compare instants (regardless of timezone)
if ($warsaw1->isBefore($warsaw2)) {
    echo "warsaw1 is before warsaw2";
}

// warsaw1 and tokyo represent the same instant
if ($warsaw1->equals($tokyo)) {
    echo "Same point in time";
}
```

## Formatting

```php
$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, ZoneId::of('Europe/Warsaw'));

// ISO-8601 extended format with timezone
echo $zdt; // "2024-03-15T14:30:00+01:00[Europe/Warsaw]"
```

## Examples

### International Meeting

```php
// Schedule meeting in New York time
$nyZone = ZoneId::of('America/New_York');
$meeting = ZonedDateTime::of(2024, 6, 15, 10, 0, 0, 0, $nyZone);

// What time in Tokyo?
$tokyoTime = $meeting->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));
echo "NY: " . $meeting;       // 2024-06-15T10:00:00-04:00[America/New_York]
echo "Tokyo: " . $tokyoTime;  // 2024-06-15T23:00:00+09:00[Asia/Tokyo]
```

### Flight Tracking

```php
// Departure from Warsaw
$departure = ZonedDateTime::of(2024, 6, 15, 10, 0, 0, 0, ZoneId::of('Europe/Warsaw'));

// Flight duration: 12 hours 30 minutes
$duration = new Duration(hours: 12, minutes: 30);

// Calculate arrival in Tokyo (same instant + duration)
$arrivalInstant = $departure->toInstant()->plus($duration);
$arrival = ZonedDateTime::ofInstant($arrivalInstant, ZoneId::of('Asia/Tokyo'));

echo "Departs: " . $departure; // 2024-06-15T10:00:00+02:00[Europe/Warsaw]
echo "Arrives: " . $arrival;    // 2024-06-16T01:30:00+09:00[Asia/Tokyo]
```

### Working Hours Across Timezones

```php
function isWorkingHoursInTimezone(ZonedDateTime $zdt, ZoneId $targetZone): bool
{
    // Convert to target timezone
    $converted = $zdt->withZoneSameInstant($targetZone);
    $hour = $converted->dateTime->hour;
    $dayOfWeek = $converted->dateTime->toLocalDate()->dayOfWeek->value;
    
    // Monday-Friday, 9 AM - 5 PM
    return $dayOfWeek < 5 && $hour >= 9 && $hour < 17;
}

$warsaw = ZonedDateTime::of(2024, 3, 15, 18, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
$nyZone = ZoneId::of('America/New_York');

if (isWorkingHoursInTimezone($warsaw, $nyZone)) {
    echo "Coworkers in NY are still at work";
}
```

## Best Practices

1. **Use for user-facing times**: When displaying times to users in different timezones
2. **Store as Instant**: For database storage, convert to `Instant` (UTC)
3. **Handle DST carefully**: Be aware of gaps and overlaps during transitions
4. **Test edge cases**: Always test around DST transition dates
5. **User timezone preference**: Store user's preferred timezone for display

## Common Gotchas

### DST Transitions

```php
// Be careful with arithmetic during DST transitions
$zone = Zone Id::of('Europe/Warsaw');

// Day before DST starts (clock moves forward 1 hour)
$before = ZonedDateTime::of(2024, 3, 30, 14, 0, 0, 0, $zone);

// Adding 24 hours is NOT the same as adding 1 day
$plus24h = $before->plusHours(24);  // 2024-03-31T15:00 (clock moved forward)
$plus1d = $before->plusDays(1);     // 2024-03-31T14:00 (same local time)
```

### Timezone vs Offset

```php
// ZonedDateTime knows about DST
$zoned = ZonedDateTime::of(2024, 1, 15, 12, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
echo $zoned->offset; // +01:00 (winter)

$zoned = ZonedDateTime::of(2024, 7, 15, 12, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
echo $zoned->offset; // +02:00 (summer, DST)
```

## See Also

- [ZoneId](zone-id.md) - Timezone identifier
- [Offset](offset.md)  - UTC offset
- [OffsetDateTime](offset-datetime.md) - Fixed offset alternative
- [Instant](instant.md) - UTC point in time
