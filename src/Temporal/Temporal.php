<?php declare(strict_types=1);

namespace Brzuchal\DateTime\Temporal;

/**
 * Rich temporal abstraction that exposes required fields and factory conversion from other temporal.
 */
interface Temporal extends TemporalAccessor
{
    /**
     * Lists the temporal fields that must be present to materialize this temporal type.
     *
     * @return array<int, TemporalField>
     */
    public static function requires(): array;

    public static function from(TemporalAccessor $accessor): static;

    public function adjust(TemporalAdjuster $adjuster): static;
}
