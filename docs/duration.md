# Duration

`Duration` represents a time-based amount, such as "2 hours and 30 minutes" or "45 seconds".

## Overview

Duration measures time in hours, minutes, seconds, and nanoseconds. Unlike `Period` which is date-based, `Duration` is always exact.

```php
use Brzuchal\DateTime\Duration;

$duration = new Duration(hours: 2, minutes: 30);
echo $duration; // "PT2H30M"
```

## Creating Duration

### From Components

```php
use Brzuchal\DateTime\Duration;

// Hours only
$duration = new Duration(hours: 2);

// Hours and minutes
$duration = new Duration(hours: 2, minutes: 30);

// All components
$duration = new Duration(
    hours: 1,
    minutes: 30,
    seconds: 45,
    nanos: 123456789
);

// Negative duration
$duration = new Duration(hours: -2, minutes: -30);
```

### From Seconds

```php
// Create from total seconds
$duration = Duration::ofSeconds(3600);  // 1 hour
$duration = Duration::ofSeconds(7200);  // 2 hours
```

### Zero Duration

```php
$zero = Duration::zero();
echo $zero; // "PT0S"
```

### From String (ISO-8601)

```php
// Parse ISO-8601 duration string
$duration = Duration::parse('PT2H30M');     // 2 hours 30 minutes
$duration = Duration::parse('PT1H30M45S');  // 1:30:45
$duration = Duration::parse('PT45S');       // 45 seconds
```

## Accessing Components

```php
$duration = new Duration(hours: 2, minutes: 30, seconds: 45);

echo $duration->hours;   // 2
echo $duration->minutes; // 30
echo $duration->seconds; // 45
echo $duration->nanos;   // 0
```

## Arithmetic Operations

### Adding Durations

```php
$d1 = new Duration(hours: 2, minutes: 30);
$d2 = new Duration(hours: 1, minutes: 15);

$total = $d1->plusDuration($d2);
// Result: 3 hours 45 minutes
```

### Subtracting Durations

```php
$d1 = new Duration(hours: 3, minutes: 30);
$d2 = new Duration(hours: 1, minutes: 15);

$diff = $d1->minusDuration($d2);
// Result: 2 hours 15 minutes
```

### Negation

```php
$duration = new Duration(hours: 2, minutes: 30);
$negated = $duration->negated();
// Result: -2 hours -30 minutes
```

### Multiplication

```php
$duration = new Duration(hours: 1, minutes: 30);
$doubled = $duration->multipliedBy(2);
// Result: 3 hours
```

### Division

```php
$duration = new Duration(hours: 3);
$half = $duration->dividedBy(2);
// Result: 1 hour 30 minutes
```

## Comparison

```php
$d1 = new Duration(hours: 2);
$d2 = new Duration(minutes: 120);

// Check equality (compares total time)
if ($d1->equalTo($d2)) {
    echo "Same duration"; // true
}

// Check if zero
if ($duration->isZero()) {
    echo "No duration";
}
```

## Conversion

### To Total Seconds

```php
$duration = new Duration(hours: 2, minutes: 30);
$seconds = $duration->toSeconds();
// Result: 9000 (2*3600 + 30*60)
```

### To Components

```php
$duration = Duration::ofSeconds(9045); // 2:30:45

// Break down into components
$hours = intdiv($duration->toSeconds(), 3600);
$remainder = $duration->toSeconds() % 3600;
$minutes = intdiv($remainder, 60);
$seconds = $remainder % 60;

echo "$hours:$minutes:$seconds"; // "2:30:45"
```

## Formatting

```php
$duration = new Duration(hours: 2, minutes: 30, seconds: 45);

// ISO-8601 format
echo $duration; // "PT2H30M45S"

$duration = new Duration(seconds: 45);
echo $duration; // "PT45S"

$zero = Duration::zero();
echo $zero; // "PT0S"
```

## Using with LocalDateTime

```php
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\Duration;

$dt = LocalDateTime::of(2024, 3, 15, 10, 0);
$duration = new Duration(hours: 2, minutes: 30);

$future = $dt->plus($duration);
// Result: 2024-03-15T12:30:00
```

## Examples

### Measuring Elapsed Time

```php
$start = LocalDateTime::now();

// Do some work...
sleep(2);

$end = LocalDateTime::now();

// Calculate duration (approximation)
$elapsed = new Duration(
    seconds: $end->toLocalTime()->toSecondOfDay() - 
             $start->toLocalTime()->toSecondOfDay()
);

echo "Elapsed: " . $elapsed;
```

### Converting Units

```php
// Minutes to duration
function minutesToDuration(int $minutes): Duration
{
    return new Duration(minutes: $minutes);
}

// Hours to duration
function hoursToDuration(float $hours): Duration
{
    $wholeHours = (int) $hours;
    $minutes = (int) (($hours - $wholeHours) * 60);
    return new Duration(hours: $wholeHours, minutes: $minutes);
}

$duration = hoursToDuration(2.5); // 2 hours 30 minutes
```

### Time Budget

```php
$totalBudget = new Duration(hours: 8); // 8 hour workday
$used = new Duration(hours: 5, minutes: 30);
$remaining = $totalBudget->minusDuration($used);

echo "Remaining: " . $remaining; // PT2H30M
```

### Meeting Duration

```php
function formatMeetingDuration(Duration $duration): string
{
    $hours = $duration->hours;
    $minutes = $duration->minutes;
    
    if ($hours > 0 && $minutes > 0) {
        return "{$hours}h {$minutes}m";
    } elseif ($hours > 0) {
        return "{$hours}h";
    } else {
        return "{$minutes}m";
    }
}

$meeting = new Duration(hours: 1, minutes: 30);
echo formatMeetingDuration($meeting); // "1h 30m"
```

## Best Practices

1. **Use for time amounts**: Perfect for durations, intervals, timeouts
2. **Avoid for calendar calculations**: Use `Period` for dates (days, months, years)
3. **Immutability**: All operations return new instances
4. **Precision**: Supports nanosecond precision
5. **ISO-8601**: Standard format for serialization

## Duration vs Period

| Feature | Duration | Period |
|---------|----------|--------|
| Units | Hours, minutes, seconds, nanos | Years, months, days |
| Precision | Exact (time-based) | Calendar-based (variable) |
| Example | 2 hours 30 minutes | 1 month 5 days |
| Use case | Timers, timeouts, elapsed time | Birthdays, deadlines, age |

```php
// Duration - always exact
$duration = new Duration(hours: 24);
// Always exactly 24 hours

// Period - calendar based
$period = new Period(days: 1);
// One day - could be 23, 24, or 25 hours (DST)
```

## See Also

- [Period](period.md) - Date-based periods
- [DateTimeDelta](date-time-delta.md) - Combined duration and period
- [LocalDateTime](local-datetime.md) - Using durations with date-time
