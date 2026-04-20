# Period

`Period` represents a date-based amount, such as "2 years", "3 months", or "10 days".

## Overview

Period measures time in calendar units (years, months, days). Unlike `Duration`, periods are not always exact due to varying month lengths and leap years.

```php
use Brzuchal\DateTime\Period;

$period = new Period(years: 1, months: 2, days: 15);
echo $period; // "P1Y2M15D"
```

## Creating Period

### From Components

```php
use Brzuchal\DateTime\Period;

// Years only
$period = new Period(years: 2);

// Months only
$period = new Period(months: 6);

// All components
$period = new Period(years: 1, months: 3, days: 15);

// Negative period
$period = new Period(years: -1, months: -2);
```

### Zero Period

```php
$zero = Period::zero();
echo $zero; // "P0D"
```

### From String (ISO-8601)

```php
// Parse ISO-8601 period string
$period = Period::parse('P1Y');        // 1 year
$period = Period::parse('P2M');        // 2 months  
$period = Period::parse('P15D');       // 15 days
$period = Period::parse('P1Y2M15D');   // 1 year, 2 months, 15 days
```

## Accessing Components

```php
$period = new Period(years: 1, months: 2, days: 15);

echo $period->years;  // 1
echo $period->months; // 2
echo $period->days;   // 15
```

## Arithmetic Operations

### Adding Periods

```php
$p1 = new Period(years: 1, months: 2);
$p2 = new Period(months: 3, days: 5);

$total = $p1->plusPeriod($p2);
// Result: 1 year, 5 months, 5 days
```

### Subtracting Periods

```php
$p1 = new Period(years: 2, months: 6);
$p2 = new Period(months: 3);

$diff = $p1->minusPeriod($p2);
// Result: 2 years, 3 months
```

### Negation

```php
$period = new Period(years: 1, months: 2, days: 15);
$negated = $period->negated();
// Result: -1 year, -2 months, -15 days
```

## Normalization

Periods can be normalized to adjust values within standard ranges:

```php
$period = new Period(months: 15); // 15 months

// Normalize to years and months
$normalized = $period->normalized();
// Result: 1 year, 3 months
```

## Comparison

```php
$p1 = new Period(months: 12);
$p2 = new Period(years: 1);

// Check if zero
if ($period->isZero()) {
    echo "No period";
}

// Note: Direct comparison is complex due to variable month lengths
// Use with dates for accurate comparison
```

## Using with LocalDate

```php
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\Period;

$date = LocalDate::of(2024, 1, 15);
$period = new Period(years: 1, months: 2, days: 10);

$future = $date->plusPeriod($period);
// Result: 2025-03-25

$past = $date->minusPeriod($period);
// Result: 2022-11-05
```

## Formatting

```php
$period = new Period(years: 1, months: 2, days: 15);
echo $period; // "P1Y2M15D"

$period = new Period(years: 2);
echo $period; // "P2Y"

$period = new Period(months: 6, days: 10);
echo $period; // "P6M10D"

$zero = Period::zero();
echo $zero; // "P0D"
```

## Calendar Arithmetic Gotchas

### Month Roll-over

```php
$date = LocalDate::of(2024, 1, 31);
$period = new Period(months: 1);

$result = $date->plusPeriod($period);
// Result: 2024-02-29 (Feb has 29 days in 2024)
// Not 2024-02-31 (doesn't exist)
```

### Leap Years

```php
$date = LocalDate::of(2024, 2, 29); // Leap year
$period = new Period(years: 1);

$result = $date->plusPeriod($period);
// Result: 2025-02-28 (2025 is not a leap year)
```

## Examples

### Age Calculation

```php
$birthDate = LocalDate::of(1990, 5, 15);
$today = LocalDate::now();

$age = $birthDate->until($today);
echo "Age: {$age->years} years, {$age->months} months";
```

### Subscription Period

```php
$startDate = LocalDate::of(2024, 1, 1);
$subscription = new Period(months: 12); // 1 year subscription

$endDate = $startDate->plusPeriod($subscription);
// Result: 2025-01-01
```

### Trial Period

```php
function addTrialPeriod(LocalDate $signupDate): LocalDate
{
    $trial = new Period(days: 30);
    return $signupDate->plusPeriod($trial);
}

$signup = LocalDate::now();
$trialEnd = addTrialPeriod($signup);
echo "Trial ends: " . $trialEnd;
```

### Billing Cycles

```php
function nextBillingDate(LocalDate $lastBilling, string $frequency): LocalDate
{
    return match($frequency) {
        'monthly' => $lastBilling->plusPeriod(new Period(months: 1)),
        'quarterly' => $lastBilling->plusPeriod(new Period(months: 3)),
        'yearly' => $lastBilling->plusPeriod(new Period(years: 1)),
        default => $lastBilling,
    };
}

$last = LocalDate::of(2024, 1, 15);
$next = nextBillingDate($last, 'monthly');
// Result: 2024-02-15
```

## Best Practices

1. **Use for calendar calculations**: Perfect for birthdays, anniversaries, subscription periods
2. **Avoid for exact time**: Use `Duration` for exact time amounts
3. **Handle edge cases**: Be aware of month roll-over and leap year issues
4. **Normalization**: Normalize when displaying or comparing
5. **ISO-8601**: Standard format for serialization

## Period vs Duration

| Feature | Period | Duration |
|---------|--------|----------|
| Units | Years, months, days | Hours, minutes, seconds |
| Precision | Calendar-based (variable) | Exact (time-based) |
| Example | 1 month | 30 days (exact) |
| Month length | Varies (28-31 days) | N/A |
| Use case | Birthdays, subscriptions | Timers, elapsed time |

```php
// Period - calendar aware
$period = new Period(months: 1);
$jan = LocalDate::of(2024, 1, 31)->plusPeriod($period);
// Feb 29, 2024 (Feb has 29 days in leap year)

$feb = LocalDate::of(2024, 2, 29)->plusPeriod($period);
// March 29, 2024 (March has 31 days, keeps day 29)

// Duration - always exact
$duration = new Duration(hours: 720); // 30 * 24 hours
// Always exactly 720 hours, regardless of month
```

## See Also

- [Duration](duration.md) - Time-based amounts
- [DateTimeDelta](date-time-delta.md) - Combined period and duration
- [LocalDate](local-date.md) - Using periods with dates
