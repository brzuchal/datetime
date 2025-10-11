# Brzuchal\DateTime

📅 **Brzuchal\DateTime** is a lightweight, dependency-free date handling library for PHP, inspired by `java.time`.  
It provides immutable date objects with clear and type-safe APIs — without relying on PHP’s `ext-date`.

## 📦 Installation

To install via [Composer](https://getcomposer.org/), run:

```bash
composer require brzuchal/datetime
```

## ✅ Features

### LocalDate

Represents an ISO-8601 date (proleptic Gregorian) without time or timezone.

```php
use Brzuchal\DateTime\LocalDate;

$date = LocalDate::of(2023, 5, 10);

echo $date->year;    // 2023
echo $date->month;   // 5
echo $date->day;     // 10
echo $date->era;  // Brzuchal\DateTime\CalendarSystems\IsoEra::CommonEra

echo (string) $date; // "2023-05-10"
```

### Date arithmetic
```
$date = LocalDate::of(2023, 5, 10);

$date->plusDays(5);     // 2023-05-15
$date->minusDays(3);    // 2023-05-07
$date->plusMonths(1);   // 2023-06-10
$date->plusYears(2);    // 2025-05-10
```

### Period object
Use Period to encapsulate year/month/day offsets.

```
use Brzuchal\DateTime\Period;

$period = Period::of(years: 1, months: 2, days: 10);
$date = LocalDate::of(2023, 5, 10);

$date->plus($period);  // 2024-07-20
$date->minus($period); // 2022-03-00 → adjusted to 2022-02-28
```

### Lazy-computed epochDay
The epochDay property (number of days since 1970-01-01) is calculated on demand only:

```
$date = LocalDate::of(2023, 5, 10);
echo $date->epochDay; // 19182
```

### Built-in date validation
The constructor checks:
* that month is in range 1–12,
* that day is valid for the given month (including leap years).

### ISO-only, ext-date free core
LocalDate and friends are intentionally ISO-only, backed by static ISO calendar logic.
No PHP `ext-date` types are used anywhere.

### Calendar internals
- `IsoCalendar` encapsulates proleptic Gregorian math; it exposes static helpers for conversions.
- Shared Gregorian/Julian calculations live in `BaseGJCalendar`, keeping algorithms reusable for future calendars without exposing dynamic state or registries.
- Era information is represented by the `IsoEra` enum and surfaced lazily on `LocalDate` instances.

### 🛣 Roadmap
* `LocalTime`, `LocalDateTime`
* `ZonedDateTime` with timezone support
* Full `TemporalField`/`TemporalAccessor` support
* Range types like `DateInterval` or `DateRange`

### 📜 License

MIT License

Copyright (c) 2025 Michał Brzuchalski

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
