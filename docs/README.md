# DateTime Library Documentation

A comprehensive, immutable date-time library for PHP 8.4+, inspired by Java's `java.time` and C#'s NodaTime.

## Table of Contents

### Core Concepts
- [Getting Started](getting-started.md)
- [Design Philosophy](design-philosophy.md)

### Date and Time Types
- [LocalDate](local-date.md) - Date without time or timezone
- [LocalTime](local-time.md) - Time without date or timezone
- [LocalDateTime](local-datetime.md) - Date and time without timezone
- [Instant](instant.md) - Point in time (UTC timestamp)

### Timezone-Aware Types
- [Offset](offset.md) - UTC offset (e.g., +02:00)
- [OffsetDateTime](offset-datetime.md) - Date-time with fixed offset
- [ZoneId](zone-id.md) - Timezone identifier (e.g., Europe/Warsaw)
- [ZonedDateTime](zoned-datetime.md) - Date-time with timezone and DST awareness

### Duration and Period
- [Duration](duration.md) - Time-based duration (hours, minutes, seconds)
- [Period](period.md) - Date-based period (years, months, days)
- [DateTimeDelta](date-time-delta.md) - Combined period and duration

### Formatting and Parsing
- [Formatting](formatting.md) - Converting date-time objects to strings
- [Parsing](parsing.md) - Creating date-time objects from strings

### Advanced Topics
- [Temporal](temporal.md) - Temporal field system
- [Exceptions](exceptions.md) - Error handling

## Quick Example

```php
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\ZonedDateTime;
use Brzuchal\DateTime\ZoneId;

// Create a local date-time
$dateTime = LocalDateTime::of(2024, 3, 15, 14, 30);
echo $dateTime; // "2024-03-15T14:30:00"

// Add timezone awareness
$warsaw = ZonedDateTime::ofLocal($dateTime, ZoneId::of('Europe/Warsaw'));
echo $warsaw; // "2024-03-15T14:30:00+01:00[Europe/Warsaw]"

// Convert to different timezone
$tokyo = $warsaw->withZoneSameInstant(ZoneId::of('Asia/Tokyo'));
echo $tokyo; // "2024-03-15T22:30:00+09:00[Asia/Tokyo]"
```

## Installation

```bash
composer require brzuchal/datetime
```

## Requirements

- PHP 8.4 or higher
- ext-json (for serialization)

## License

MIT License - see LICENSE file for details.
