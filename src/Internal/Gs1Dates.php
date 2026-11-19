<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use DateTimeImmutable;
use DateTimeZone;

/**
 * @internal
 *
 * GS1 `YYMMDD` dates: the century comes from the sliding window around the current year, and `DD = 00` (where the AI
 * allows it) is the last day of the month
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, sections 3.4.2 and 7.12 (Figure 7-17)
 */
final class Gs1Dates
{
    /**
     * Figure 7-17: YY minus the current year's last two digits is 51 to 99 → previous century, -50 to -99 → next
     * century, otherwise the current century.
     *
     * @param int<0, 99> $yy
     */
    public static function year(int $yy, int $currentYear): int
    {
        $century = $currentYear - $currentYear % 100;
        $difference = $yy - $currentYear % 100;
        if ($difference >= 51) {
            return $century - 100 + $yy;
        }

        if ($difference <= -50) {
            return $century + 100 + $yy;
        }

        return $century + $yy;
    }

    /**
     * Whether `$yyyymmdd` (eight digits) is a calendar date; with `$dayZero`, day `00` also passes.
     */
    public static function isDate(string $yyyymmdd, bool $dayZero): bool
    {
        if (\strlen($yyyymmdd) !== 8 || \strspn($yyyymmdd, Gs1Charsets::DIGITS) !== 8) {
            return false;
        }

        $year = (int) \substr($yyyymmdd, 0, 4);
        $month = (int) \substr($yyyymmdd, 4, 2);
        $day = (int) \substr($yyyymmdd, 6, 2);

        if ($dayZero && $day === 0) {
            return $month >= 1 && $month <= 12;
        }

        return \checkdate($month, $day, $year);
    }

    /**
     * `$yymmdd` with its century from `$currentYear`, as `YYYYMMDD`; null for anything but six digits.
     */
    public static function expand(string $yymmdd, int $currentYear): ?string
    {
        if (\strlen($yymmdd) !== 6 || \strspn($yymmdd, Gs1Charsets::DIGITS) !== 6) {
            return null;
        }

        return \sprintf('%04d', self::year(\max(0, \min(99, (int) \substr($yymmdd, 0, 2))), $currentYear)) . \substr($yymmdd, 2);
    }

    /**
     * The date at 00:00:00 UTC, the century chosen relative to the UTC year of `$today`; null when that century
     * gives no such calendar day.
     */
    public static function toDate(string $yymmdd, DateTimeImmutable $today): ?DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        $yyyymmdd = self::expand($yymmdd, (int) $today->setTimezone($utc)->format('Y'));
        if ($yyyymmdd === null || !self::isDate($yyyymmdd, true)) {
            return null;
        }

        $year = (int) \substr($yyyymmdd, 0, 4);
        $month = (int) \substr($yyyymmdd, 4, 2);
        $day = (int) \substr($yyyymmdd, 6, 2);
        if ($day === 0) {
            $day = (int) new DateTimeImmutable(\sprintf('%04d-%02d-01', $year, $month), $utc)->format('t');
        }

        return new DateTimeImmutable(\sprintf('%04d-%02d-%02dT00:00:00', $year, $month, $day), $utc);
    }
}
