<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Gs1\Internal\UdiDates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UdiDates::class)]
final class UdiDatesTest extends TestCase
{
    public function testCalendarDatesAreUtcWithTheirHour(): void
    {
        $date = UdiDates::calendar(2027, 11, 23, 23);

        self::assertNotNull($date);
        self::assertSame('2027-11-23T23:00:00+00:00', $date->format(DATE_ATOM));
        self::assertSame('UTC', $date->getTimezone()->getName());
        self::assertSame('2027-11-23T00:00:00+00:00', UdiDates::calendar(2027, 11, 23)?->format(DATE_ATOM));
    }

    public function testImpossibleCalendarDatesAreNull(): void
    {
        self::assertNull(UdiDates::calendar(2027, 2, 29));
        self::assertNull(UdiDates::calendar(2027, 13, 1));
        self::assertNull(UdiDates::calendar(2027, 1, 1, 24));
        self::assertInstanceOf(DateTimeImmutable::class, UdiDates::calendar(2028, 2, 29));
    }

    public function testMonthIsItsLastDay(): void
    {
        self::assertSame('2028-02-29', UdiDates::month(2028, 2)?->format('Y-m-d'));
        self::assertSame('2027-02-28', UdiDates::month(2027, 2)?->format('Y-m-d'));
        self::assertSame('2027-12-31', UdiDates::month(2027, 12)?->format('Y-m-d'));
        self::assertNull(UdiDates::month(2027, 0));
    }

    public function testOrdinalDaysCountFromTheFirstOfJanuary(): void
    {
        self::assertSame('2027-01-01T00:00:00+00:00', UdiDates::ordinal(2027, 1)?->format(DATE_ATOM));
        self::assertSame('2027-12-31T05:00:00+00:00', UdiDates::ordinal(2027, 365, 5)?->format(DATE_ATOM));
        self::assertSame('2028-12-31', UdiDates::ordinal(2028, 366)?->format('Y-m-d'));
        self::assertSame('2028-02-29', UdiDates::ordinal(2028, 60)?->format('Y-m-d'));
        self::assertNull(UdiDates::ordinal(2027, 366));
        self::assertNull(UdiDates::ordinal(2027, 0));
        self::assertNull(UdiDates::ordinal(2027, 1, 24));
        self::assertInstanceOf(DateTimeImmutable::class, UdiDates::ordinal(2027, 1, 23));
    }
}
