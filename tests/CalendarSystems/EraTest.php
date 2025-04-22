<?php

namespace Tests\CalendarSystems;

use Brzuchal\DateTime\CalendarSystems\Era;
use PHPUnit\Framework\TestCase;

final class EraTest extends TestCase
{
    public function testEpochDayIsIncludedInEra(): void
    {
        $era = new Era(0, 'TE', 'TestEra', 0, 365);

        self::assertTrue($era->includesEpochDay(1));
    }

    public function testEpochDayIsNotIncludedInEra(): void
    {
        $era = new Era(0, 'TE', 'TestEra', 0, 365);

        self::assertFalse($era->includesEpochDay(366));
    }

    public function testEqualityOfTwoEqualEras(): void
    {
        $era1 = new Era(0, 'TE', 'TestEra');
        $era2 = new Era(0, 'TE', 'TestEra');

        self::assertTrue($era1->equals($era2));
    }

    public function testInequalityOfTwoDifferentEras(): void
    {
        $era1 = new Era(0, 'TE', 'TestEra1');
        $era2 = new Era(1, 'TE2', 'TestEra2');

        self::assertFalse($era1->equals($era2));
    }

    public function testEraBefore(): void
    {
        $era1 = new Era(0, 'TE', 'TestEra1', endEpochDay: -1);
        $era2 = new Era(1, 'TE2', 'TestEra2', startEpochDay: 0);

        self::assertTrue($era1->isBefore($era2));
    }

    public function testEraAfter(): void
    {
        $era1 = new Era(0, 'TE', 'TestEra1', endEpochDay: -1);
        $era2 = new Era(1, 'TE2', 'TestEra2', startEpochDay: 0);

        self::assertTrue($era2->isAfter($era1));
    }
}
