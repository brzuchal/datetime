# DateTimeDelta

`DateTimeDelta` combines a `Period` (date-based) and a `Duration` (time-based) into a single amount.

## Overview

Use `DateTimeDelta` when you need to represent both calendar-based (years, months, days) and time-based (hours, minutes, seconds) amounts together.

```php
use Brzuchal\DateTime\DateTimeDelta;
use Brzuchal\DateTime\Period;
use Brzuchal\DateTime\Duration;

$period = new Period(months: 1, days: 5);
$duration = new Duration(hours: 2, minutes: 30);

$delta = new DateTimeDelta($period, $duration);
echo $delta; // "P1M5DT2H30M"
```

## Creating DateTimeDelta

### From Period and Duration

```php
use Brzuchal\DateTime\DateTimeDelta;
use Brzuchal\DateTime\Period;
use Brzuchal\DateTime\Duration;

$period = new Period(years: 1, months: 2, days: 15);
$duration = new Duration(hours: 3, minutes: 45);

$delta = new DateTimeDelta($period, $duration);
```

### Using of() Factory

```php
$period = new Period(months: 6);
$duration = new Duration(hours: 12);

$delta = DateTimeDelta::of($period, $duration);
```

### Empty Delta

```php
$empty = DateTimeDelta::empty();
// Period: 0, Duration: 0
```

### From String (ISO-8601)

```php
// Parse combined ISO-8601 duration string
$delta = DateTimeDelta::parse('P1M5DT2H30M');
// Period: 1 month, 5 days
// Duration: 2 hours, 30 minutes

$delta = DateTimeDelta::parse('P1YT12H');
// Period: 1 year
// Duration: 12 hours
```

## Accessing Components

```php
$period = new Period(months: 1, days: 5);
$duration = new Duration(hours: 2, minutes: 30);
$delta = new DateTimeDelta($period, $duration);

// Access period
$p = $delta->period;
echo $p->months; // 1
echo $p->days;   // 5

// Access duration
$d = $delta->duration;
echo $d->hours;   // 2
echo $d->minutes; // 30
```

## Arithmetic Operations

### Adding Deltas

```php
$delta1 = new DateTimeDelta(
    new Period(months: 1, days: 5),
    new Duration(hours: 2, minutes: 30)
);

$delta2 = new DateTimeDelta(
    new Period(days: 10),
    new Duration(hours: 1, minutes: 15)
);

$total = $delta1->plus($delta2);
// Period: 1 month, 15 days
// Duration: 3 hours, 45 minutes
```

### Subtracting Deltas

```php
$delta1 = new DateTimeDelta(
    new Period(months: 2),
    new Duration(hours: 5)
);

$delta2 = new DateTimeDelta(
    new Period(months: 1),
    new Duration(hours: 2)
);

$diff = $delta1->minus($delta2);
// Period: 1 month
// Duration: 3 hours
```

### Negation

```php
$delta = new DateTimeDelta(
    new Period(months: 1, days: 5),
    new Duration(hours: 2)
);

$negated = $delta->negated();
// Period: -1 month, -5 days
// Duration: -2 hours
```

### Multiplication

```php
$delta = new DateTimeDelta(
    new Period(months: 1, days: 2),
    new Duration(hours: 1, minutes: 30)
);

$doubled = $delta->multipliedBy(2);
// Period: 2 months, 4 days
// Duration: 3 hours
```

## Using with LocalDateTime

```php
use Brzuchal\DateTime\LocalDateTime;

$dt = LocalDateTime::of(2024, 1, 15, 10, 0);
$delta = new DateTimeDelta(
    new Period(months: 1, days: 5),
    new Duration(hours: 2, minutes: 30)
);

$future = $dt->plusDelta($delta);
// Result: 2024-02-20T12:30:00
// (1 month + 5 days in calendar, then + 2:30 hours)

$past = $dt->minusDelta($delta);
// Result: 2023-12-10T07:30:00
```

## Formatting

```php
$delta = new DateTimeDelta(
    new Period(years: 1, months: 2, days: 15),
    new Duration(hours: 3, minutes: 45, seconds: 30)
);

// ISO-8601 format
echo $delta; // "P1Y2M15DT3H45M30S"

// Only period
$delta = new DateTimeDelta(new Period(months: 6), new Duration());
echo $delta; // "P6M"

// Only duration
$delta = new DateTimeDelta(new Period(), new Duration(hours: 2));
echo $delta; // "PT2H"

// Empty
$empty = DateTimeDelta::empty();
echo $empty; // "PT0S"
```

## Examples

### Task Estimation

```php
// Est imate a task: 1 month 2 weeks, plus 5 working days of 8 hours each
$period = new Period(months: 1, days: 14);
$duration = new Duration(hours: 40); // 5 * 8 hours

$estimate = new DateTimeDelta($period, $duration);

$start = LocalDateTime::of(2024, 1, 15, 9, 0);
$deadline = $start->plusDelta($estimate);
```

### Service Level Agreement (SLA)

```php
function calculateSLA(): DateTimeDelta
{
    // SLA: 30 days + 4 business hours
    $period = new Period(days: 30);
    $duration = new Duration(hours: 4);
    
    return new DateTimeDelta($period, $duration);
}

$ticketCreated = LocalDateTime::now();
$sla = calculateSLA();
$dueDate = $ticketCreated->plusDelta($sla);

echo "SLA due: " . $dueDate;
```

### Rental Period

```php
// Rent for 2 months and 3 days, with 2-hour grace period
$period = new Period(months: 2, days: 3);
$grace = new Duration(hours: 2);

$rental = new DateTimeDelta($period, $grace);

$start = LocalDateTime::of(2024, 1, 1, 10, 0);
$end = $start->plusDelta($rental);
// End: 2024-03-04T12:00:00
```

## Best Practices

1. **Use for complex intervals**: When you need both calendar and time components
2. **Separate concerns**: Keep period and duration logic separate
3. **ISO-8601 format**: Use for serialization and storage
4. **Immutability**: All operations return new instances
5. **Clarity**: Prefer separate Period/Duration if only one is needed

## When to Use DateTimeDelta

### Use DateTimeDelta When:
- You need both calendar and time components
- Serializing complex time intervals
- Working with compound durations

### Use Period When:
- Only calendar units are needed (birthdays, subscriptions)
- Date arithmetic without time

### Use Duration When:
- Only time units are needed (timers, timeouts)
- Exact time measurements

## Examples of Each

```php
// DateTimeDelta - both calendar and time
$delta = DateTimeDelta::parse('P1M15DT2H30M');
$dt->plusDelta($delta); // Add 1 month 15 days and 2:30 hours

// Period - calendar only
$period = new Period(months: 1, days: 15);
$date->plusPeriod($period); // Add1 month 15 days (no time component)

// Duration - time only
$duration = new Duration(hours: 2, minutes: 30);
$dt->plus($duration); // Add 2:30 hours (no date component)
```

## See Also

- [Period](period.md) - Date-based periods
- [Duration](duration.md) - Time-based durations
- [LocalDateTime](local-datetime.md) - Using with date-time
