<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Format;

enum DateTimeFormat: string
{
    case IsoLocalDate = 'Y-m-d';
    case ExtendedIsoLocalDate = 'u-m-d';
    case IsoLocalTime = 'H:i:s';
}
