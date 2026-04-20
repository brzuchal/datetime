# ZoneId

`ZoneId` represents a timezone identifier, such as "Europe/Warsaw" or "America/New_York".

## Overview

ZoneId wraps an IANA timezone identifier and provides access to timezone rules including DST transitions.

```php
use Brzuchal\DateTime\ZoneId;

$zone = ZoneId::of('Europe/Warsaw');
echo $zone; // "Europe/Warsaw"
```

## Creating ZoneId

### From IANA Identifier

```php
// Create from timezone name
$warsaw = ZoneId::of('Europe/Warsaw');
$newYork = ZoneId::of('America/New_York');
$tokyo = ZoneId::of('Asia/Tokyo');

// Throws InvalidZoneId if timezone doesn't exist
try {
    $invalid = ZoneId::of('Invalid/Timezone');
} catch (InvalidZoneId $e) {
    echo "Unknown timezone";
}
```

### From Offset

```php
use Brzuchal\DateTime\Offset;

// Create zone from fixed offset
$offset = Offset::of(2, 0);
$zone = ZoneId::ofOffset($offset);

echo $zone; // "+02:00"
```

### UTC

```php
$utc = ZoneId::UTC();
echo $utc; // "UTC"
```

### System Default

```php
// Get system's default timezone
$system = ZoneId::systemDefault();
// Returns timezone from PHP's date_default_timezone_get()
```

## Getting Timezone Rules

### Offset for Timestamp

```php
$zone = ZoneId::of('Europe/Warsaw');
$timestamp = time();

// Get offset in seconds for given UTC timestamp
$offsetSeconds = $zone->getOffsetForTimestamp($timestamp);

// Get Offset object
$offset = $zone->getZoneOffsetForTimestamp($timestamp);
echo $offset; // "+01:00" or "+02:00" depending on DST
```

### Timezone Rules

```php
$zone = ZoneId::of('Europe/Warsaw');

// Get underlying timezone rules
$rules = $zone->getRules();
// ZoneRules object with transition information
```

## Comparison

```php
$zone1 = ZoneId::of('Europe/Warsaw');
$zone2 = ZoneId::of('Europe/Warsaw');
$zone3 = ZoneId::of('America/New_York');

if ($zone1->equals($zone2)) {
    echo "Same timezone"; // true
}

if (!$zone1->equals($zone3)) {
    echo "Different timezones"; // true
}
```

## Using with ZonedDateTime

```php
use Brzuchal\DateTime\ZonedDateTime;

$zone = ZoneId::of('Europe/Warsaw');

// Create zoned date-time
$zdt = ZonedDateTime::of(2024, 3, 15, 14, 30, 0, 0, $zone);

// Current time in timezone
$now = ZonedDateTime::now($zone);
```

## Common Timezone Examples

### Europe

```php
$london = ZoneId::of('Europe/London');      // GMT/BST
$paris = ZoneId::of('Europe/Paris');        // CET/CEST
$warsaw = ZoneId::of('Europe/Warsaw');      // CET/CEST
$athens = ZoneId::of('Europe/Athens');      // EET/EEST
$moscow = ZoneId::of('Europe/Moscow');      // MSK
```

### Americas

```php
$newYork = ZoneId::of('America/New_York');  // EST/EDT
$chicago = ZoneId::of('America/Chicago');   // CST/CDT
$denver = ZoneId::of('America/Denver');     // MST/MDT
$losAngeles = ZoneId::of('America/Los_Angeles'); // PST/PDT
$toronto = ZoneId::of('America/Toronto');   // EST/EDT
```

### Asia/Pacific

```php
$tokyo = ZoneId::of('Asia/Tokyo');          // JST
$shanghai = ZoneId::of('Asia/Shanghai');    // CST
$dubai = ZoneId::of('Asia/Dubai');          // GST
$sydney = ZoneId::of('Australia/Sydney');   // AEDT/AEST
$auckland = ZoneId::of('Pacific/Auckland'); // NZDT/NZST
```

## Examples

### Multi-Timezone Application

```php
// Store user's preferred timezone
class User
{
    public function __construct(
        public string $name,
        public ZoneId $timezone,
    ) {}
}

$user = new User('John', ZoneId::of('America/New_York'));

// Display time in user's timezone
$serverTime = ZonedDateTime::now(ZoneId::UTC());
$userTime = $serverTime->withZone SameInstant($user->timezone);

echo "Server: " . $serverTime;
echo "User: " . $userTime;
```

### Meeting Scheduler

```php
function scheduleMeeting(array $attendees, LocalDateTime $proposedTime): void
{
    $baseZone = ZoneId::of('Europe/Warsaw');
    $meeting = ZonedDateTime::ofLocal($proposedTime, $baseZone);
    
    echo "Meeting proposed for: " . $proposedTime . "\n";
    
    foreach ($attendees as $name => $timezone) {
        $zone = ZoneId::of($timezone);
        $localTime = $meeting->withZoneSameInstant($zone);
        echo "$name ($timezone): " . $localTime->dateTime . "\n";
    }
}

$attendees = [
    'Alice' => 'Europe/Warsaw',
    'Bob' => 'America/New_York',
    'Charlie' => 'Asia/Tokyo',
];

scheduleMeeting($attendees, LocalDateTime::of(2024, 6, 15, 14, 0));
```

### Timezone Converter

```php
function convertTimezone(
    ZonedDateTime $dateTime,
    ZoneId $targetZone
): ZonedDateTime {
    return $dateTime->withZoneSameInstant($targetZone);
}

$warsaw = ZonedDateTime::of(2024, 6, 15, 10, 0, 0, 0, ZoneId::of('Europe/Warsaw'));
$tokyo = convertTimezone($warsaw, ZoneId::of('Asia/Tokyo'));

echo "Warsaw: " . $warsaw;
echo "Tokyo: " . $tokyo;
```

## DST and Timezone Rules

### Checking DST

```php
$zone = ZoneId::of('Europe/Warsaw');

// Get offset for winter (no DST)
$winter = strtotime('2024-01-15 12:00:00');
$winterOffset = $zone->getOffsetForTimestamp($winter);
echo $winterOffset; // 3600 (+01:00)

// Get offset for summer (DST)
$summer = strtotime('2024-07-15 12:00:00');
$summerOffset = $zone->getOffsetForTimestamp($summer);
echo $summerOffset; // 7200 (+02:00)
```

### Transition Dates

Different timezones have different DST transition dates:

```php
// Europe: Usually last Sunday of March/October
$europe = ZoneId::of('Europe/Warsaw');

// US: Usually second Sunday of March, first Sunday of November
$us = ZoneId::of('America/New_York');

// Some zones don't observe DST
$arizona = ZoneId::of('America/Phoenix'); // No DST
$tokyo = ZoneId::of('Asia/Tokyo');        // No DST
```

## Best Practices

1. **Store timezone separately**: In databases, store UTC time + timezone identifier
2. **User preference**: Let users choose their timezone
3. **Validation**: Always validate timezone identifiers from external sources
4. **System default**: Use `systemDefault()` for server-local operations
5. **Fixed offsets**: Use `Offset` and `OffsetDateTime` when timezone rules aren't needed

## Common Gotchas

### Timezone vs Offset

```php
// ZoneId contains rules (DST, transitions)
$zone = ZoneId::of('Europe/Warsaw');
// Knows about DST: +01:00 in winter, +02:00 in summer

// Offset is just a number
$offset = Offset::of(1, 0);
// Always +01:00, no DST knowledge
```

### PHP's timezone vs Offset

```php
// Don't confuse with PHP's timezone offset
// PHP uses timezone names, not numeric offsets

// CORRECT - IANA timezone
$zone = ZoneId::of('Europe/Warsaw');

// For fixed offset, use Offset class
$offset = Offset::of(2, 0);
$zone = ZoneId::ofOffset($offset);
```

## See Also

- [ZonedDateTime](zoned-datetime.md) - Using timezones with date-time
- [Offset](offset.md) - Fixed UTC offset
- [OffsetDateTime](offset-datetime.md) - Date-time with offset
