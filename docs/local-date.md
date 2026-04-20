# LocalDate

`LocalDate` represents a date without time or timezone information, such as "2024-03-15".

## Creating LocalDate

### From Components

```php
use Brzuchal\DateTime\LocalDate;

// Basic creation
$date = LocalDate::of(2024, 3, 15);

// Current date
$today = LocalDate::now();
```

### From Epoch Day

```php
// Epoch day = days since 1970-01-01
$date = LocalDate::fromEpochDay(19797); // 2024-03-15
```

### From Parsing

```php
use Brzuchal\DateTime\Format\DateTimeFormat;

$formatter = DateTimeFormat::ofPattern('yyyy-MM-dd');
$date = LocalDate::parse('2024-03-15', $formatter);
```

## Accessing Components

```php
$date = LocalDate::of(2024, 3, 15);

echo $date->year;   // 2024
echo $date->month;  // 3
echo $date->day;    // 15

echo $date->dayOfWeek; // DayOfWeek enum
echo $date->dayOfYear; // 75 (day number in year)
echo $date->epochDay;  // 19797 (days since 1970-01-01)
```

## Modifying LocalDate

All modification methods return a new `LocalDate` instance:

### Adding/Subtracting

```php
$date = LocalDate::of(2024, 3, 15);

// Add time units
$future = $date->plusDays(7);      // 2024-03-22
$future = $date->plusMonths(2);    // 2024-05-15
$future = $date->plusYears(1);     // 2025-03-15

// Subtract time units
$past = $date->minusDays(7);       // 2024-03-08
$past = $date->minusMonths(2);     // 2024-01-15
$past = $date->minusYears(1);      // 2023-03-15
```

### Adding Period

```php
use Brzuchal\DateTime\Period;

$date = LocalDate::of(2024, 3, 15);
$period = new Period(years: 1, months: 2, days: 10);

$future = $date->plusPeriod($period); // 2025-05-25
```

### Setting Components

```php
$date = LocalDate::of(2024, 3, 15);

// Set specific component
$modified = $date->withYear(2025);   // 2025-03-15
$modified = $date->withMonth(12);    // 2024-12-15
$modified = $date->withDay(1);       // 2024-03-01

// Chain modifications
$newDate = $date->withYear(2025)->withMonth(12)->withDay(25);
// Result: 2025-12-25
```

## Validation

### Checking Validity

```php
// Check if a date is valid
$isValid = LocalDate::isValid(2024, 2, 29); // true (leap year)
$isValid = LocalDate::isValid(2023, 2, 29); // false (not a leap year)
```

### Leap Year

```php
$date = LocalDate::of(2024, 3, 15);

if ($date->isLeapYear()) {
    echo "2024 is a leap year";
}
```

## Comparing Dates

```php
$date1 = LocalDate::of(2024, 3, 15);
$date2 = LocalDate::of(2024, 3, 20);

// Boolean comparisons
if ($date1->isBefore($date2)) {
    echo "date1 is before date2";
}

if ($date2->isAfter($date1)) {
    echo "date2 is after date1";
}

if ($date1->equalTo($date1)) {
    echo "Dates are equal";
}

// Numeric comparison
$result = $date1->compareTo($date2); // -1 (before), 0 (equal), or 1 (after)
```

## Getting Differences

```php
$start = LocalDate::of(2024, 3, 15);
$end = LocalDate::of(2024, 5, 20);

$period = $start->until($end);
// Period: 2 months, 5 days
```

## Formatting

```php
$date = LocalDate::of(2024, 3, 15);

// ISO-8601 format (default)
echo $date; // "2024-03-15"

// Custom format
use Brzuchal\DateTime\Format\DateTimeFormat;

$formatter = DateTimeFormat::ofPattern('dd/MM/yyyy');
echo $formatter->format($date); // "15/03/2024"
```

## Combining with Time

```php
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\LocalDateTime;

$date = LocalDate::of(2024, 3, 15);
$time = LocalTime::of(14, 30);

$dateTime = $date->atTime($time);
// Result: LocalDateTime 2024-03-15T14:30:00
```

## Temporal Adjusters

```php
use Brzuchal\DateTime\Temporal\TemporalAdjuster;

$date = LocalDate::of(2024, 3, 15);

// First day of month
$first = $date->with(TemporalAdjuster::firstDayOfMonth());
// Last day of month
$last = $date->with(TemporalAdjuster::lastDayOfMonth());

// First day of next month
$nextMonth = $date->with(TemporalAdjuster::firstDayOfNextMonth());
```

## Exceptions

### InvalidDate

Thrown when creating an invalid date:

```php
use Brzuchal\DateTime\InvalidDate;

try {
    $date = LocalDate::of(2024, 13, 1); // Invalid month
} catch (InvalidDate $e) {
    echo "Invalid date: " . $e->getMessage();
}
```

## Best Practices

1. **Use for business logic**: `LocalDate` is perfect for birthdays, holidays, and business dates
2. **Avoid for timestamps**: Use `Instant` or `ZonedDateTime` for actual points in time
3. **Immutability**: Always assign the result of modification methods to a variable
4. **Validation**: Use `isValid()` before creating dates from user input

## Examples

### Age Calculation

```php
$birthDate = LocalDate::of(1990, 5, 15);
$today = LocalDate::now();

$period = $birthDate->until($today);
echo "Age: " . $period->years . " years";
```

### Business Days

```php
function addBusinessDays(LocalDate $date, int $days): LocalDate
{
    $result = $date;
    while ($days > 0) {
        $result = $result->plusDays(1);
        // Skip weekends (Saturday = 5, Sunday = 6)
        if ($result->dayOfWeek->value < 5) {
            $days--;
        }
    }
    return $result;
}
```

### Date Range

```php
function isInRange(LocalDate $date, LocalDate $start, LocalDate $end): bool
{
    return !$date->isBefore($start) && !$date->isAfter($end);
}

$date = LocalDate::of(2024, 3, 15);
$start = LocalDate::of(2024, 3, 1);
$end = LocalDate::of(2024, 3, 31);

if (isInRange($date, $start, $end)) {
    echo "Date is in range";
}
```
