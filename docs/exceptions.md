# Exceptions

This library uses specific exception types for different error scenarios, making it easier to handle errors appropriately.

## Exception Hierarchy

All exceptions extend from PHP's `\Exception`:

```
\Exception
├── InvalidDate
├── InvalidTime
├── InvalidOffset
├── InvalidZoneId
├── InvalidZoneRules
├── InvalidFormat
├── InvalidDuration
├── InsufficientDateComponents
└── InsufficientTimeComponents
```

## Date and Time Validation

### InvalidDate

Thrown when creating an invalid date:

```php
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\InvalidDate;

try {
    $date = LocalDate::of(2024, 13, 1); // Invalid month
} catch (InvalidDate $e) {
    echo "Error: " . $e->getMessage();
    // "Month is out of range: 13"
}

try {
    $date = LocalDate::of(2024, 2, 30); // February doesn't have 30 days
} catch (InvalidDate $e) {
    echo "Error: " . $e->getMessage();
    // "Day is out of range for month"
}
```

### InvalidTime

Thrown when creating an invalid time:

```php
use Brzuchal\DateTime\LocalTime;
use Brzuchal\DateTime\InvalidTime;

try {
    $time = LocalTime::of(25, 0); // Invalid hour
} catch (InvalidTime $e) {
    echo "Error: " . $e->getMessage();
    // "Hour is out of range: 25"
}

try {
    $time = LocalTime::of(14, 75); // Invalid minute
} catch (InvalidTime $e) {
    echo "Error: " . $e->getMessage();
    // "Minute is out of range: 75"
}
```

## Timezone and Offset Errors

### InvalidOffset

Thrown when creating an invalid UTC offset:

```php
use Brzuchal\DateTime\Offset;
use Brzuchal\DateTime\InvalidOffset;

try {
    $offset = Offset::of(20, 0); // Out of valid range (-18 to +18)
} catch (InvalidOffset $e) {
    echo "Error: " . $e->getMessage();
    // "Zone offset hours not in valid range: 20"
}

try {
    $offset = Offset::of(5, -30); // Sign mismatch
} catch (InvalidOffset $e) {
    echo "Error: " . $e->getMessage();
    // "Zone offset minutes and seconds must be positive for positive hours"
}

try {
    $offset = Offset::parse('invalid'); // Invalid format
} catch (InvalidOffset $e) {
    echo "Error: " . $e->getMessage();
    // "Invalid zone offset format: invalid"
}
```

### InvalidZoneId

Thrown when a timezone identifier is not found:

```php
use Brzuchal\DateTime\ZoneId;
use Brzuchal\DateTime\InvalidZoneId;

try {
    $zone = ZoneId::of('Invalid/Timezone');
} catch (InvalidZoneId $e) {
    echo "Error: " . $e->getMessage();
    // "Unknown time-zone ID: Invalid/Timezone"
}
```

### InvalidZoneRules

Thrown when there are issues with timezone data files:

```php
use Brzuchal\DateTime\InvalidZoneRules;

// This exception is typically thrown internally when:
// - Timezone data file is corrupted
// - Timezone data file format is invalid
// - Timezone data file cannot be read
```

## Parsing Errors

### InvalidFormat

Thrown when parsing fails:

```php
use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\Format\DateTimeFormat;
use Brzuchal\DateTime\Format\InvalidFormat;

try {
    $formatter = DateTimeFormat::ofPattern('yyyy-MM-dd');
    $date = LocalDate::parse('invalid-date', $formatter);
} catch (InvalidFormat $e) {
    echo "Error: " . $e->getMessage();
    // "Unable to parse date/time from \"invalid-date\""
}
```

### InvalidDuration

Thrown when parsing an invalid duration string:

```php
use Brzuchal\DateTime\Duration;
use Brzuchal\DateTime\InvalidDuration;

try {
    $duration = Duration::parse('invalid');
} catch (InvalidDuration $e) {
    echo "Error: " . $e->getMessage();
}
```

## Component Insufficiency

### InsufficientDateComponents

Thrown when parsed data doesn't contain enough information to create a date:

```php
use Brzuchal\DateTime\InsufficientDateComponents;

// Thrown internally when temporal query doesn't have required fields
try {
    // Parsing that only extracts month/day but not year
    $date = LocalDate::from($accessor); // Missing year
} catch (InsufficientDateComponents $e) {
    echo "Error: Not enough date information";
}
```

### InsufficientTimeComponents

Thrown when parsed data doesn't contain enough information to create a time:

```php
use Brzuchal\DateTime\InsufficientTimeComponents;

// Thrown internally when temporal query doesn't have required fields
try {
    $time = LocalTime::from($accessor); // Missing hour/minute
} catch (InsufficientTimeComponents $e) {
    echo "Error: Not enough time information";
}
```

## Error Handling Best Practices

### Validate User Input

```php
function createDateFromUserInput(string $dateString): ?LocalDate
{
    try {
        $formatter = DateTimeFormat::ofPattern('yyyy-MM-dd');
        return LocalDate::parse($dateString, $formatter);
    } catch (InvalidFormat $e) {
        // Log error and return null or show user-friendly message
        error_log("Invalid date format: " . $e->getMessage());
        return null;
    } catch (InvalidDate $e) {
        // Date parsed but invalid (e.g., Feb 30)
        error_log("Invalid date: " . $e->getMessage());
        return null;
    }
}
```

### Handle Timezone Errors

```php
function getUserTimezone(string $timezoneId): ZoneId
{
    try {
        return ZoneId::of($timezoneId);
    } catch (InvalidZoneId $e) {
        // Fall back to UTC if user's timezone is invalid
        error_log("Invalid timezone: {$timezoneId}, using UTC");
        return ZoneId::UTC();
    }
}
```

### Graceful Degradation

```php
function parseOptionalDateTime(string $input): ?LocalDateTime
{
    if (empty($input)) {
        return null;
    }
    
    try {
        $formatter = DateTimeFormat::ofPattern('yyyy-MM-dd HH:mm:ss');
        return LocalDateTime::parse($input, $formatter);
    } catch (InvalidFormat | InvalidDate | InvalidTime $e) {
        // Log and return null instead of crashing
        error_log("Failed to parse date-time: " . $e->getMessage());
        return null;
    }
}
```

### Type-Specific Handling

```php
function handleDateTimeCreation(array $data): void
{
    try {
        $date = LocalDate::of($data['year'], $data['month'], $data['day']);
        $time = LocalTime::of($data['hour'], $data['minute']);
        $dateTime = LocalDateTime::ofDateAndTime($date, $time);
        
        // Success
        processDateTime($dateTime);
        
    } catch (InvalidDate $e) {
        echo "Invalid date: Please check year, month, and day values.";
    } catch (InvalidTime $e) {
        echo "Invalid time: Please check hour and minute values.";
    } catch (\Exception $e) {
        echo "Unexpected error: " . $e->getMessage();
    }
}
```

## Common Error Scenarios

### Leap Year Confusion

```php
try {
    // Feb 29 in non-leap year
    $date = LocalDate::of(2023, 2, 29);
} catch (InvalidDate $e) {
    // Handle: 2023 is not a leap year
}
```

### Month Boundaries

```php
try {
    // Months only go to 12
    $date = LocalDate::of(2024, 13, 1);
} catch (InvalidDate $e) {
    // Handle: Month 13 doesn't exist
}

try {
    // Day 31 doesn't exist in all months
    $date = LocalDate::of(2024, 4, 31); // April has 30 days
} catch (InvalidDate $e) {
    // Handle: April only has 30 days
}
```

### Time Boundaries

```php
try {
    // Hours are 0-23
    $time = LocalTime::of(24, 0);
} catch (InvalidTime $e) {
    // Handle: Hour must be 0-23
}

try {
    // Minutes/seconds are 0-59
    $time = LocalTime::of(14, 60);
} catch (InvalidTime $e) {
    // Handle: Minute must be 0-59
}
```

### Offset Range

```php
try {
    // Offset must be within ±18 hours
    $offset = Offset::of(19, 0);
} catch (InvalidOffset $e) {
    // Handle: Offset out of valid range
}
```

## Debugging Tips

### Add Context to Errors

```php
function createDateWithContext(int $year, int $month, int $day, string $context): LocalDate
{
    try {
        return Local Date::of($year, $month, $day);
    } catch (InvalidDate $e) {
        throw new \RuntimeException(
            "Failed to create date for {$context}: {$e->getMessage()}",
            0,
            $e
        );
    }
}

try {
    $birthDate = createDateWithContext(1990, 13, 1, "user birthday");
} catch(\RuntimeException $e) {
    echo $e->getMessage();
    // "Failed to create date for user birthday: Month is out of range: 13"
}
```

### Log for Analysis

```php
function safeParseDate(string $input): ?LocalDate
{
    try {
        return LocalDate::parse($input);
    } catch (InvalidFormat $e) {
        error_log(sprintf(
            "Date parsing failed - Input: %s, Error: %s, Trace: %s",
            $input,
            $e->getMessage(),
            $e->getTraceAsString()
        ));
        return null;
    }
}
```

## Exception Summary

| Exception | When Thrown | Common Causes |
|-----------|-------------|---------------|
| `InvalidDate` | Invalid date creation | Month > 12, Feb 30, Day 32 |
| `InvalidTime` | Invalid time creation | Hour > 23, Minute > 59 |
| `InvalidOffset` | Invalid offset | Out of range, sign mismatch, bad format |
| `InvalidZoneId` | Unknown timezone | Typo, non-existent zone |
| `InvalidZoneRules` | Timezone data error | Corrupt files, missing data |
| `InvalidFormat` | Parsing failure | Wrong format, malformed input |
| `InvalidDuration` | Duration parsing | Invalid ISO-8601 duration |
| `InsufficientDateComponents` | Missing date fields | Incomplete parse result |
| `InsufficientTimeComponents` | Missing time fields | Incomplete parse result |

## See Also

- [Getting Started](getting-started.md) - Basic usage patterns
- [LocalDate](local-date.md) - Date validation
- [LocalTime](local-time.md) - Time validation
- [Offset](offset.md) - Offset validation
- [ZoneId](zone-id.md) - Timezone validation
