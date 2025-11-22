<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

use Brzuchal\DateTime\LocalDate;
use Brzuchal\DateTime\LocalDateTime;
use Brzuchal\DateTime\LocalTime;

/**
 * Collection of common temporal queries returning closures mirroring java.time's TemporalQueries.
 */
final class TemporalQueries
{
    /**
     * @return callable(TemporalAccessor):(LocalDate|null)
     */
    public static function localDate(): callable
    {
        return static function (TemporalAccessor $accessor): LocalDate|null {
            if (! $accessor->supports(...LocalDate::requires())) {
                return null;
            }

            return LocalDate::from($accessor);
        };
    }

    /**
     * @return callable(TemporalAccessor):(LocalTime|null)
     */
    public static function localTime(): callable
    {
        return static function (TemporalAccessor $accessor): LocalTime|null {
            if (! $accessor->supports(...LocalTime::requires())) {
                return null;
            }

            return LocalTime::from($accessor);
        };
    }

    /**
     * @return callable(TemporalAccessor):(LocalDateTime|null)
     */
    public static function localDateTime(): callable
    {
        return static function (TemporalAccessor $accessor): LocalDateTime|null {
            if (! $accessor->supports(...LocalDateTime::requires())) {
                return null;
            }

            return LocalDateTime::from($accessor);
        };
    }
}
