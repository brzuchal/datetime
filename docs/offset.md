# Offset

`Offset` represents a fixed UTC offset, such as "+02:00" or "-05:00".

## Overview

An offset is the difference between local time and UTC. For example, "+02:00" means the local time is 2 hours ahead of UTC.

```php
use Brzuchal\DateTime\Offset;

$offset = Offset::of(2, 0);
echo $offset; // "+02:00"
```

## Creating Offset

### From Hours and Minutes

```php
use Brzuchal\DateTime\Offset;

// Hours only
$offset = Offset::of(2);        // +02:00
$offset = Offset::of(-5);       // -05:00

// Hours and minutes
$offset = Offset::of(5, 30);    // +05:30
$offset = Offset::of(-3, -30);  // -03:30

// Hours, minutes, and seconds (rare)
$offset = Offset::of(0, 0, 30); // +00:00:30
```

### From Total Seconds

```php
// Create from total offset in seconds
$offset = Offset::ofTotalSeconds(7200);  // +02:00 (2  hours * 3600)
$offset = Offset::ofTotalSeconds(19800); // +05:30 (5.5 hours * 3600)
```

### From String

```php
// Parse from ISO-8601 offset string
$offset = Offset::parse('+02:00');
$offset = Offset::parse('-05:30');
$offset = Offset::parse('Z');        // UTC (zero offset)

// Various formats supported
$offset = Offset::parse('+02');      // +02:00
$offset = Offset::parse('+0200');    // +02:00
$offset = Offset::parse('+02:00:00'); // +02:00:00
```

### UTC

```php
// Get UTC offset (zero)
$utc = Offset::utc();
echo $utc; // "Z"
echo $utc->totalSeconds; // 0
```

## Accessing Components

```php
$offset = Offset::of(2, 30, 15);

echo $offset->totalSeconds; // 9015 (2*3600 + 30*60 + 15)
```

## Comparing Offsets

```php
$offset1 = Offset::of(2, 0);
$offset2 = Offset::of(5, 0);

// Equality
if ($offset1->equalTo($offset2)) {
    echo "Offsets are equal";
}

// Comparison
$result = $offset1->compareTo($offset2); 
// -1 (offset1 < offset2), 0 (equal), or 1 (offset1 > offset2)

if ($offset1->compareTo($offset2) < 0) {
    echo "offset1 is less than offset2";
}
```

## Formatting

```php
$offset = Offset::of(2, 0);
echo $offset; // "+02:00"

$offset = Offset::of(2, 30);
echo $offset; // "+02:30"

$offset = Offset::of(2, 30, 45);
echo $offset; // "+02:30:45" (with seconds)

$offset = Offset::utc();
echo $offset; // "Z"
```

## Valid Range

Offsets must be within the range of -18:00 to +18:00:

```php
use Brzuchal\DateTime\InvalidOffset;

try {
    $offset = Offset::of(20, 0); // Out of range
} catch (InvalidOffset $e) {
    echo "Invalid offset: " . $e->getMessage();
}

// Valid range
$min = Offset::of(-18, 0);  // -18:00
$max = Offset::of(18, 0);   // +18:00
```

## Examples

### Common Timezones

```php
// Common offsets around the world
$utc = Offset::utc();           // Z (UTC)
$gmt = Offset::of(0, 0);        // +00:00 (GMT)
$est = Offset::of(-5, 0);       // -05:00 (EST)
$cet = Offset::of(1, 0);        // +01:00 (CET)
$ist = Offset::of(5, 30);       // +05:30 (India Standard Time)
$acst = Offset::of(9, 30);      // +09:30 (Australian Central)
```

### Calculating Time Difference

```php
$warsaw = Offset::of(1, 0);  // +01:00
$newYork = Offset::of(-5, 0); // -05:00

$diff = $warsaw->totalSeconds - $newYork->totalSeconds;
$hoursDiff = $diff / 3600;
echo "Time difference: {$hoursDiff} hours"; // 6 hours
```

### Offset from System Timezone

```php
use Brzuchal\DateTime\ZoneId;
use Brzuchal\DateTime\Instant;

$systemZone = ZoneId::systemDefault();
$instant = Instant::now();

$offset = $systemZone->getZoneOffsetForTimestamp($instant->epochSecond);
echo "Current system offset: " . $offset;
```

## Using with OffsetDateTime

```php
use Brzuchal\DateTime\OffsetDateTime;

$offset = Offset::of(2, 0);
$odt = OffsetDateTime::of(2024, 3, 15, 14, 30, 0, 0, $offset);

echo $odt; // "2024-03-15T14:30:00+02:00"
```

## Using with ZoneId

```php
use Brzuchal\DateTime\ZoneId;

// Create ZoneId from offset
$offset = Offset::of(2, 0);
$zoneId = ZoneId::ofOffset($offset);

echo $zoneId; // "+02:00"
```

## Best Practices

1. **Use named constants**: For common offsets, create constants
2. **Validation**: Always validate offsets from external sources
3. **UTC default**: When in doubt, use UTC
4. **Avoid manual calculation**: Use library methods instead of calculating seconds manually

## Common Gotchas

### Sign Convention

```php
// Positive offset = ahead of UTC (east)
$tokyo = Offset::of(9, 0);  // +09:00 (9 hours ahead)

// Negative offset = behind UTC (west)
$newYork = Offset::of(-5, 0); // -05:00 (5 hours behind)
```

### Minutes Must Match Sign

```php
// CORRECT
$offset = Offset::of(5, 30);   // +05:30
$offset = Offset::of(-5, -30); // -05:30

// INCORRECT - will throw InvalidOffset
try {
    $offset = Offset::of(5, -30);  // Mixed signs not allowed
} catch (InvalidOffset $e) {
    echo "Error: Minutes must match hours sign";
}
```

### Offset is Not Timezone

```php
// Offset is just a number - no DST awareness
$winter = Offset::of(1, 0);
$summer = Offset::of(1, 0);
// Same offset year-round

// Compare to ZoneId which handles DST
$zone = ZoneId::of('Europe/Warsaw');
// Winter: +01:00, Summer: +02:00 (DST)
```

## See Also

- [OffsetDateTime](offset-datetime.md) - Date-time with offset
- [ZoneId](zone-id.md) - Timezone identifier
- [ZonedDateTime](zoned-datetime.md) - With DST awareness
