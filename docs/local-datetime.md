# LocalDateTime

`LocalDateTime` combines a date and time without timezone information, such as "2024-03-15T14:30:00".

## Creating LocalDateTime

### From Components

```php
use Brzuchal\DateTime\LocalDateTime;

// Create with year, month, day, hour, minute
$dt = LocalDateTime::of(2024, 3, 15, 14, 30);

// With seconds and nanoseconds
$dt = LocalDateTime::of(2024, 3, 15, 14, 30, 45, 123456789);

// Current date-time
$now = LocalDateTime::now();
```

### From LocalDate and LocalTime

```php
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalTime;

$date = LocalDate::of(2024, 3, 15);
$time = LocalTime::of(14, 30);

$dateTime = LocalDateTime::ofDateAndTime($date, $time);
```

### From Instant

```php
use Brzuchal\DateTime\Instant;

$instant = Instant::now();
$dateTime = LocalDateTime::ofInstant($instant);
```

## Accessing Components

```php
$dt = LocalDateTime::of(2024, 3, 15, 14, 30, 45, 123456789);

// Date components
echo $dt->year;   // 2024
echo $dt->month;  // 3
echo $dt->day;    // 15

// Time components
echo $dt->hour;   // 14
echo $dt->minute; // 30
echo $dt->second; // 45
echo $dt->nano;   // 123456789

// Access underlying objects
$date = $dt->toLocalDate();
$time = $dt->toLocalTime();
```

## Modifying LocalDateTime

### Adding/Subtracting Time

```php
$dt = LocalDateTime::of(2024, 3, 15, 14, 30);

// Add/subtract date components
$future = $dt->plusYears(1);      // 2025-03-15T14:30:00
$future = $dt->plusMonths(2);     // 2024-05-15T14:30:00
$future = $dt->plusDays(7);       // 2024-03-22T14:30:00

// Add/subtract time components
$future = $dt->plusHours(3);      // 2024-03-15T17:30:00
$future = $dt->plusMinutes(45);   // 2024-03-15T15:15:00
$future = $dt->plusSeconds(30);   // 2024-03-15T14:30:30
$future = $dt->plusNanos(1000);   // 2024-03-15T14:30:00.000001

// Time overflow adjusts date
$dt = LocalDateTime::of(2024, 3, 15, 23, 30);
$next = $dt->plusHours(2); // 2024-03-16T01:30:00
```

### Adding Duration and Period

```php
use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\Period;
use Brzuchal\DateTime\DateTimeDelta;

$dt = LocalDateTime::of(2024, 3, 15, 14, 30);

// Add duration (time-based)
$duration = new Duration(hours: 2, minutes: 30);
$future = $dt->plus($duration);

// Add period (date-based)
$period = new Period(months: 1, days: 5);
$future = $dt->plusPeriod($period);

// Add both via DateTimeDelta
$delta = new DateTimeDelta($period, $duration);
$future = $dt->plusDelta($delta);
```

### Setting Components

```php
$dt = LocalDateTime::of(2024, 3, 15, 14, 30);

// Set date components
$modified = $dt->withDate(LocalDate::of(2025, 1, 1));

// Set time components (selective)
$modified = $dt->with(hour: 16, minute: 45);
// Result: 2024-03-15T16:45:00

// Set specific time component
$modified = $dt->with(second: 30);
// Result: 2024-03-15T14:30:30
```

## Comparing

```php
$dt1 = LocalDateTime::of(2024, 3, 15, 10, 0);
$dt2 = LocalDateTime::of(2024, 3, 15, 14, 0);

if ($dt1->isBefore($dt2)) {
    echo "dt1 is earlier than dt2";
}

if ($dt1->equalTo($dt1)) {
    echo "Date-times are equal";
}

$comparison = $dt1->compareTo($dt2); // -1, 0, or 1
```

## Formatting

```php
$dt = LocalDateTime::of(2024, 3, 15, 14, 30, 45);

// ISO-8601 format (default)
echo $dt; // "2024-03-15T14:30:45"

// Without seconds if zero
$dt = LocalDateTime::of(2024, 3, 15, 14, 30, 0);
echo $dt; // "2024-03-15T14:30:00"

// With nanoseconds
$dt = LocalDateTime::of(2024, 3, 15, 14, 30, 45, 123456789);
echo $dt; // "2024-03-15T14:30:45.123456789"
```

## Serialization

```php
$dt = LocalDateTime::of(2024, 3, 15, 14, 30);

// Serialize
$serialized = serialize($dt);

// Unserialize
$restored = unserialize($serialized);
```

## Examples

### Event Scheduling

```php
// Schedule event at specific local time
$event = LocalDateTime::of(2024, 12, 25, 19, 0);
echo "Event: " . $event; // "2024-12-25T19:00:00"

// Remind 1 hour before
$reminder = $event->plusHours(-1);
echo "Reminder: " . $reminder; // "2024-12-25T18:00:00"
```

### Business Hours

```php
function isBusinessHours(LocalDateTime $dt): bool
{
    $hour = $dt->hour;
    $dayOfWeek = $dt->toLocalDate()->dayOfWeek->value;
    
    // Monday-Friday, 9 AM - 5 PM
    return $dayOfWeek < 5 && $hour >= 9 && $hour < 17;
}

$dt = LocalDateTime::of(2024, 3, 15, 14, 30);
if (isBusinessHours($dt)) {
    echo "During business hours";
}
```

### Time Tracking

```php
$start = LocalDateTime::of(2024, 3, 15, 9, 0);
$end = LocalDateTime::of(2024, 3, 15, 17, 30);

$duration = new Duration(
    hours: $end->hour - $start->hour,
    minutes: $end->minute - $start->minute
);

echo "Worked: " . $duration; // "PT8H30M"
```

## Best Practices

1. **Use for local events**: Perfect for calendar events, appointments, schedules
2. **Avoid for distributed systems**: Use `ZonedDateTime` or `Instant` for systems across timezones
3. **Immutability**: Always capture the result of modification methods
4. **Null safety**: Use type hints to ensure non-null values

## See Also

- [LocalDate](local-date.md) - Date component
- [LocalTime](local-time.md) - Time component
- [ZonedDateTime](zoned-datetime.md) - With timezone awareness
- [Duration](duration.md) - Time-based amounts
- [Period](period.md) - Date-based amounts
