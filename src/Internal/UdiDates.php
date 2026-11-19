<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;

/**
 * @internal
 *
 * Calendar and ordinal (Julian) dates of HIBC and ISBT 128 data, as `DateTimeImmutable` in UTC; null when the date
 * does not exist
 */
final class UdiDates
{
    public static function calendar(int $year, int $month, int $day, int $hour = 0): ?DateTimeImmutable
    {
        if (!\checkdate($month, $day, $year) || $hour > 23) {
            return null;
        }

        return self::at(\sprintf('%04d-%02d-%02d %02d', $year, $month, $day, $hour));
    }

    /**
     * The last day of the month.
     */
    public static function month(int $year, int $month): ?DateTimeImmutable
    {
        return self::calendar($year, $month, 1)?->modify('last day of this month');
    }

    /**
     * Day `$ordinal` of `$year`, 1 being 1 January.
     */
    public static function ordinal(int $year, int $ordinal, int $hour = 0): ?DateTimeImmutable
    {
        $daysInYear = \checkdate(2, 29, $year) ? 366 : 365;
        if ($ordinal < 1 || $ordinal > $daysInYear || $hour > 23) {
            return null;
        }

        return self::at(\sprintf('%04d-01-01 %02d', $year, $hour))->modify(\sprintf('+%d days', $ordinal - 1));
    }

    private static function at(string $dateAndHour): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H', $dateAndHour, new DateTimeZone('UTC'));
        if ($date === false) {
            throw new LogicException('Validated date did not parse.');
        }

        return $date;
    }
}
