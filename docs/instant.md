# Instant

`Instant` represents a point in time as UTC timestamp with nanosecond precision.

## Overview

Instant is the most fundamental time type, representing an exact moment on the timeline. It's always in UTC and is the best choice for storing timestamps.

```php
use Brzuchal\DateTime\Instant;

$instant = Instant::now();
echo $instant->epochSecond; // Unix timestamp
```

## Creating Instant

### Current Time

```php
use Brzuchal\DateTime\Instant;

// Current instant
$now = Instant::now();
```

### From Epoch

```php
// From Unix timestamp (seconds since 1970-01-01 00:00:00 UTC)
$instant = Instant::ofEpochSecond(1710511800);

// With nanosecond precision
$instant = Instant::ofEpochSecond(1710511800, 123456789);

// From ticks (100-nanosecond units since epoch)
$instant = new Instant(17105118000000000);
```

### Zero Instant

```php
// Unix epoch: 1970-01-01 00:00:00 UTC
$epoch = Instant::zero();
```

## Accessing Components

```php
$instant = Instant::now();

// Unix timestamp (seconds)
echo $instant->epochSecond;

// Nanosecond adjustment (0-999,999,999)
echo $instant->nanoAdjustment;

// Ticks (100ns units since epoch)
echo $instant->ticks;

// Epoch day (days since 1970-01-01)
echo $instant->epochDay;
```

## Arithmetic Operations

### Adding Duration

```php
use Brzuchal\DateTime\Duration;

$instant = Instant::now();
$duration = new Duration(hours: 2, minutes: 30);

$future = $instant->plus($duration);
```

### Subtracting Duration

```php
$instant = Instant::now();
$duration = new Duration(hours: 1);

$past = $instant->minus($duration);
```

## Comparing

```php
$instant1 = Instant::now();
sleep(1);
$instant2 = Instant::now();

// Boolean comparisons
if ($instant1->isBefore($instant2)) {
    echo "instant1 is before instant2";
}

if ($instant2->isAfter($instant1)) {
    echo "instant2 is after instant1";
}

if ($instant1->equals($instant1)) {
    echo "Instants are equal";
}

// Numeric comparison
$result = $instant1->compareTo($instant2); // -1, 0, or 1
```

## Converting to Other Types

### To LocalDateTime (UTC)

```php
$instant = Instant::now();
$dateTime = LocalDateTime::ofInstant($instant);
// LocalDateTime in UTC
```

### To ZonedDateTime

```php
use BrzuchalDateTime\ZoneId;

$instant = Instant::now();
$zone = ZoneId::of('Europe/Warsaw');

$zdt = ZonedDateTime::ofInstant($instant, $zone);
// Time in Warsaw timezone
```

### To OffsetDateTime

```php
use Brzuchal\DateTime\Offset;

$instant = Instant::now();
$offset = Offset::of(2, 0);

$odt = OffsetDateTime::ofInstant($instant, $offset);
// Time with +02:00 offset
```

## Formatting

```php
$instant = Instant::ofEpochSecond(1710511800);

// Unix timestamp
echo $instant->epochSecond; // 1710511800

// For human-readable format, convert to LocalDateTime or ZonedDateTime
$dt = LocalDateTime::ofInstant($instant);
echo $dt; // "2024-03-15T14:30:00"
```

## Examples

### Measuring Elapsed Time

```php
$start = Instant::now();

// Perform some operation
sleep(2);

$end = Instant::now();

// Calculate duration
$elapsed = $end->epochSecond - $start->epochSecond;
echo "Elapsed: {$elapsed} seconds";
```

### Timestamp Storage

```php
// Store event timestamp
class Event
{
    public function __construct(
        public string $name,
        public Instant $occurredAt,
    ) {}
    
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'occurred_at' => $this->occurredAt->epochSecond,
        ];
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            Instant::ofEpochSecond($data['occurred_at'])
        );
    }
}

$event = new Event('User Login', Instant::now());
$stored = $event->toArray();

// Later, restore from storage
$restored = Event::fromArray($stored);
```

### API Timestamps

```php
// Generate API response with timestamp
function apiResponse(array $data): array
{
    return [
        'data' => $data,
        'timestamp' => Instant::now()->epochSecond,
    ];
}

// Process API request with timestamp validation
function validateTimestamp(int $timestamp, int $maxAge = 300): bool
{
    $requestInstant = Instant::ofEpochSecond($timestamp);
    $now = Instant::now();
    
    $age = $now->epochSecond - $requestInstant->epochSecond;
    return $age <= $maxAge;
}

if (validateTimestamp($_POST['timestamp'], 300)) {
    // Request is within 5 minutes
    processRequest();
}
```

### Time-based Ordering

```php
class LogEntry
{
    public function __construct(
        public string $message,
        public Instant $timestamp,
    ) {}
}

$logs = [
    new LogEntry('Error occurred', Instant::ofEpochSecond(1710511800)),
    new LogEntry('System started', Instant::ofEpochSecond(1710511700)),
    new LogEntry('User logged in', Instant::ofEpochSecond(1710511900)),
];

// Sort by timestamp
usort($logs, fn($a, $b) => $a->timestamp->compareTo($b->timestamp));

foreach ($logs as $log) {
    $dt = LocalDateTime::ofInstant($log->timestamp);
    echo "[{$dt}] {$log->message}\n";
}
```

## Best Practices

1. **Database storage**: Store as Unix timestamp (epoch seconds)
2. **No timezone**: Instant is always UTC, convert to ZonedDateTime for display
3. **Precision**: Use nanosecond precision when needed
4. **Comparison**: Use for unambiguous time comparison across timezones
5. **Immutability**: All operations return new instances

## Instant vs Other Types

| Type | Purpose | Timezone | Example |
|------|---------|----------|---------|
| `Instant` | Point in time (UTC) | UTC only | Event timestamp |
| `LocalDateTime` | Local date-time (no TZ) | None | Calendar appointment |
| `OffsetDateTime` | With fixed offset | Fixed | API response |
| `ZonedDateTime` | With timezone + DST | Full | User-facing time |

### When to Use Instant

```php
// ✓ GOOD use cases
$eventOccurred = Instant::now();        // Event timestamps
$createdAt = Instant::now();            // Database records
$lastModified = Instant::now();         // File metadata
$expiresAt = Instant::now()->plus(...); // Token expiration

// ✗ AVOID for these
$birthday = LocalDate::of(1990, 5, 15);      // Use LocalDate
$meeting = ZonedDateTime::of(...);            // Use ZonedDateTime
```

## Precision

### Nanosecond Precision

```php
$instant = Instant::ofEpochSecond(1710511800, 123456789);

echo $instant->epochSecond;      // 1710511800
echo $instant->nanoAdjustment;   // 123456789
```

### Ticks (100-nanosecond units)

```php
// Internal representation
$instant = new Instant(17105118001234567);

// Convert to epoch second
$seconds = intdiv($instant->ticks, 10_000_000);
```

## See Also

- [LocalDateTime](local-datetime.md) - Date-time without timezone
- [ZonedDateTime](zoned-datetime.md) - With timezone awareness
- [OffsetDateTime](offset-datetime.md) - With fixed offset
- [Duration](duration.md) - Time-based amounts
