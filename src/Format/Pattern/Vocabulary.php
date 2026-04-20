<?php

declare(strict_types=1);

namespace Brzuchal\DateTime\Format\Pattern;

final class Vocabulary
{
    public const array SUPPORTED_FIELDS = [
        'Y',
        'y',
        'X',
        'm',
        'n',
        'd',
        'j',
        'H',
        'G',
        'h',
        'g',
        'i',
        's',
        'f',
        'u',
        'v',
        'a',
        'A',
        'z',
        'o',
        'W',
        'w',
        'N',
        'L',
        't',
        'S',
        'D',
        'l',
        'M',
        'F',
    ];

    public const array MONTH_NAMES_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    public const array MONTH_NAMES_FULL = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    public const array WEEKDAY_NAMES_SHORT = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    public const array WEEKDAY_NAMES_FULL = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
}
