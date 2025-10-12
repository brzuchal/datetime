<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

enum DateTimeFormat: string
{
    // ISO-8601 family
    case IsoLocalDate = 'Y-m-d';
    case ExtendedIsoLocalDate = 'X-m-d';
    case IsoLocalTime = 'H:i:s';
    case IsoBasicLocalDate = 'Ymd';
    case IsoBasicLocalTime = 'His';
    case IsoLocalTimeNano = 'H:i:s[fffffffff]';
    case IsoBasicLocalTimeNano = 'His[fffffffff]';
    case IsoLocalDateTime = 'Y-m-d\TH:i:s[fffffffff]';
    case IsoBasicLocalDateTime = 'Ymd\THis[fffffffff]';
    case IsoLocalDateTimeSeconds = 'Y-m-d\TH:i:s';

    // case IsoInstant = 'Y-m-d\TH:i:s\Z'; // requires timezone/offset-aware accessor
    // case IsoInstantNano = 'Y-m-d\TH:i:s[fffffffff]\Z'; // requires timezone/offset-aware accessor
    case IsoWeekDate = 'o-\WW-N';
    case IsoOrdinalDate = 'Y-z';

    // SQL-style formats
    case SqlDateTime = 'Y-m-d H:i:s';
    case SqlDateTimeNano = 'Y-m-d H:i:s[fffffffff]';

    // Web/HTTP formats (UTC-only variants)
    // case HttpDate = 'D, d M Y H:i:s \G\M\T'; // requires timezone/offset-aware accessor
    // case Rfc850Date = 'l, d-M-Y H:i:s \G\M\T'; // requires timezone/offset-aware accessor
    // case Rfc3339Utc = 'Y-m-d\TH:i:s[fffffffff]+00:00'; // requires timezone/offset-aware accessor
    // case RssUtc = 'D, d M Y H:i:s +0000'; // requires timezone/offset-aware accessor
}
