<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use DateTimeImmutable;
use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcDateFormat;

/**
 * @internal
 *
 * HIBC LIC data strings: the primary data structure alone, or primary and secondary data concatenated in one symbol,
 * each ending with its Mod 43 check character
 *
 * @see https://www.hibcc.org/wp-content/uploads/SLS-2.6-Final.pdf ANSI/HIBC 2.6-2016 (R2026), sections 2.1.1, 2.2.1, 2.2.1.1, 2.3, Appendices E and F
 * @see https://web.archive.org/web/20130203212925/http://www.hibcc.org/wp-content/uploads/2012/09/ANSI-HIBC-2.3-SLS-2009.pdf ANSI/HIBC 2.3-2009, Appendix E1.1 (legacy quantity `$$8`/`$$9`)
 * @see docs/sources/GS1.md "HIBCC"
 */
final class HibcSyntax
{
    /**
     * Primary 24 + "/" + serial secondary 36 (`$$+` + `9` and a 5-digit quantity + flag `4` + 8-digit date + 18) +
     * supplemental /S 20, /16D 12, /14D 12, /Q 7 + check character: 113.
     */
    private const int MAX_LENGTH = 113;

    /** "+", LIC 4, product number up to 18, unit of measure, check character. */
    private const int PRIMARY_LENGTH = 25;

    private const string TABLE_B1 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%';

    private const string PRIMARY = '/^\+([A-Z][A-Z0-9]{3})([A-Z0-9]{1,18})([0-9])$/D';

    /** Lot and serial fields: alphanumeric, "." and "-" permitted (section 3.0). */
    private const string FIELD = '[A-Z0-9.\-]';

    /** Named groups of the `$$` / `$$+` secondary formats and the date format each carries (Appendix E1.1). */
    private const array DATE_GROUPS = [
        'mmyy' => HibcDateFormat::Mmyy,
        'mmddyy' => HibcDateFormat::Mmddyy,
        'yymmdd' => HibcDateFormat::Yymmdd,
        'yymmddhh' => HibcDateFormat::Yymmddhh,
        'yyjjj' => HibcDateFormat::Yyjjj,
        'yyjjjhh' => HibcDateFormat::Yyjjjhh,
    ];

    /** Section 2.3.2: S an1+an18, 16D and 14D an3+n8 (YYYYMMDD), Q an1+n1...n5. */
    private const array SUPPLEMENTAL = [
        'S' => '/^' . self::FIELD . '{1,18}$/D',
        '16D' => '/^[0-9]{8}$/D',
        '14D' => '/^[0-9]{8}$/D',
        'Q' => '/^[0-9]{1,5}$/D',
    ];

    public static function parse(string $value): HibcData
    {
        if (\strlen($value) > self::MAX_LENGTH) {
            throw self::fail(\sprintf('must have at most %d characters', self::MAX_LENGTH));
        }

        if (!\str_starts_with($value, '+')) {
            throw self::fail('must start with the flag character "+"');
        }

        if (self::isSecondary($value)) {
            throw self::fail('is secondary data only; pass it with its primary data to HibcUdi::fromSymbols()');
        }

        self::checkCharacter($value);
        $parts = \explode('/', \substr($value, 0, -1));
        $primary = self::primary($parts[0]);
        if (\count($parts) === 1) {
            return $primary;
        }

        return self::supplemental(\array_slice($parts, 2), self::secondary($parts[1], $primary));
    }

    /**
     * The concatenated form of a primary and a secondary symbol: the primary check character, the "+" of the secondary
     * data and its link character are dropped, "/" joins them, one check character ends the string. With supplemental
     * data the link character may stand before the check character or after the secondary data (before the first "/");
     * the reading that gives valid data is taken, and two valid readings are refused as ambiguous.
     *
     * @return non-empty-string
     */
    public static function concatenate(string $primary, string $secondary): string
    {
        if (\str_contains(\substr(self::primarySymbol($primary), 0, -1), '/')) {
            throw self::fail('primary symbol carries secondary data');
        }

        if (\strlen($secondary) > self::MAX_LENGTH || !self::isSecondary($secondary)) {
            throw self::fail('secondary symbol must start with "+" and a digit or "$"');
        }

        self::checkCharacter($secondary);
        $data = \substr($secondary, 1, -1);
        $readings = [];
        if ($data[-1] === $primary[-1]) {
            $readings[] = \substr($data, 0, -1);
        }

        // "/" is a Mod 43 value (40), so a "/" link character is itself the first "/" and a supplemental "/" follows it.
        $slash = \strpos($data, '/');
        $link = $slash === false ? -1 : ($primary[-1] === '/' ? $slash : $slash - 1);
        if ($link > 0 && $data[$link] === $primary[-1] && ($primary[-1] !== '/' || ($data[$link + 1] ?? '') === '/')) {
            $readings[] = \substr($data, 0, $link) . \substr($data, $link + 1);
        }

        if ($readings === []) {
            throw self::fail('secondary link character does not match the primary check character');
        }

        $valid = [];
        $failure = null;
        foreach ($readings as $reading) {
            $body = \substr($primary, 0, -1) . '/' . $reading;
            try {
                self::parse($body . Mod43::compute($body));
                $valid[] = $body . Mod43::compute($body);
            } catch (InvalidUdiException $exception) {
                $failure = $exception;
            }
        }

        return match (\count($valid)) {
            0 => throw $failure ?? new LogicException('No HIBC reading was tried.'),
            1 => $valid[0],
            default => throw self::fail('secondary link character position is ambiguous'),
        };
    }

    /**
     * Note 1 of Table 2: the secondary data has a digit or "$" after the flag character, the primary data a letter.
     */
    private static function isSecondary(string $value): bool
    {
        return \strlen($value) > 1 && \str_contains('0123456789$', $value[1]);
    }

    private static function primarySymbol(string $primary): string
    {
        if (\strlen($primary) > self::PRIMARY_LENGTH || !\str_starts_with($primary, '+') || self::isSecondary($primary)) {
            throw self::fail('primary symbol must start with "+" and a letter');
        }

        self::checkCharacter($primary);

        return $primary;
    }

    private static function checkCharacter(string $symbol): void
    {
        if (\strlen($symbol) < 2 || \strspn($symbol, self::TABLE_B1) !== \strlen($symbol)) {
            throw self::fail('must be characters of HIBCC Table B1 ending with a check character');
        }

        if (Mod43::compute(\substr($symbol, 0, -1)) !== $symbol[-1]) {
            throw self::fail('has a wrong Mod 43 check character');
        }
    }

    private static function primary(string $primary): HibcData
    {
        if (\preg_match(self::PRIMARY, $primary, $match) !== 1) {
            throw self::fail('primary data must be "+", a 4-character LIC starting with a letter, a 1 to 18 character product number of A-Z and 0-9, and a unit of measure digit');
        }

        return new HibcData($primary, $match[1], $match[2], \max(0, \min(9, (int) $match[3])));
    }

    private static function secondary(string $data, HibcData $primary): HibcData
    {
        $field = self::FIELD;
        if (\preg_match('/^([0-9]{5})(' . $field . '{0,13})$/D', $data, $match) === 1) {
            return self::with($primary, $match[2], null, HibcDateFormat::Yyjjj, $match[1]);
        }

        if (\preg_match('/^\$(\+?)(' . $field . '{1,18})$/D', $data, $match) === 1) {
            return $match[1] === ''
                ? self::with($primary, $match[2], null, null, '')
                : self::with($primary, null, $match[2], null, '');
        }

        // ANSI/HIBC 2.3: a quantity alone leaves the date flag and the lot field out ("+$$848GQ").
        $pattern = '/^\$\$(?<serial>\+?)(?:8(?<q2>[0-9]{2})|9(?<q5>[0-9]{5}))?'
            . '(?:(?<=[0-9]{2})$|(?:(?<mmyy>[01][0-9]{3})|2(?<mmddyy>[0-9]{6})|3(?<yymmdd>[0-9]{6})|4(?<yymmddhh>[0-9]{8})'
            . '|5(?<yyjjj>[0-9]{5})|6(?<yyjjjhh>[0-9]{7})|7)(?<text>' . $field . '{0,18})$)/D';
        if (\preg_match($pattern, $data, $match, PREG_UNMATCHED_AS_NULL) !== 1) {
            throw self::fail('secondary data must follow a format of Appendix F');
        }

        $format = null;
        $date = '';
        foreach (self::DATE_GROUPS as $group => $candidate) {
            if (isset($match[$group])) {
                $format = $candidate;
                $date = $match[$group];
            }
        }

        $quantity = $match['q2'] ?? $match['q5'];
        $data = $match['serial'] === '+'
            ? self::with($primary, null, $match['text'], $format, $date)
            : self::with($primary, $match['text'], null, $format, $date);

        return $quantity === null ? $data : new HibcData(
            $data->primary,
            $data->labelerIdentificationCode,
            $data->productNumber,
            $data->unitOfMeasure,
            $data->lot,
            $data->serial,
            $data->expiryDate,
            $data->expiryDateFormat,
            null,
            \max(0, \min(99999, (int) $quantity)),
        );
    }

    private static function with(HibcData $primary, ?string $lot, ?string $serial, ?HibcDateFormat $format, string $date): HibcData
    {
        return new HibcData(
            $primary->primary,
            $primary->labelerIdentificationCode,
            $primary->productNumber,
            $primary->unitOfMeasure,
            $lot === '' ? null : $lot,
            $serial === '' ? null : $serial,
            $format instanceof HibcDateFormat ? self::date($format, $date) : null,
            $format,
        );
    }

    /**
     * Two-digit years are 20YY: HIBCC states no century rule, and a fixed one keeps a value's meaning independent of
     * the clock.
     */
    private static function date(HibcDateFormat $format, string $digits): DateTimeImmutable
    {
        $number = static fn (int $offset, int $length): int => (int) \substr($digits, $offset, $length);
        $date = match ($format) {
            HibcDateFormat::Mmyy => UdiDates::month(2000 + $number(2, 2), $number(0, 2)),
            HibcDateFormat::Mmddyy => UdiDates::calendar(2000 + $number(4, 2), $number(0, 2), $number(2, 2)),
            HibcDateFormat::Yymmdd => UdiDates::calendar(2000 + $number(0, 2), $number(2, 2), $number(4, 2)),
            HibcDateFormat::Yymmddhh => UdiDates::calendar(2000 + $number(0, 2), $number(2, 2), $number(4, 2), $number(6, 2)),
            HibcDateFormat::Yyjjj => UdiDates::ordinal(2000 + $number(0, 2), $number(2, 3)),
            HibcDateFormat::Yyjjjhh => UdiDates::ordinal(2000 + $number(0, 2), $number(2, 3), $number(5, 2)),
            HibcDateFormat::Yyyymmdd => UdiDates::calendar($number(0, 4), $number(4, 2), $number(6, 2)),
        };

        return $date ?? throw self::fail('has a date that does not exist');
    }

    /**
     * Section 2.3: "/" + data identifier + data; DIs S (serial), 16D (date of manufacture), 14D (expiry), Q (quantity,
     * always last); each at most once.
     *
     * @param list<string> $fields
     */
    private static function supplemental(array $fields, HibcData $data): HibcData
    {
        $values = [];
        foreach ($fields as $field) {
            if (isset($values['Q'])) {
                throw self::fail('quantity must be the last supplemental data field');
            }

            $identifier = self::supplementalIdentifier($field);
            $content = \substr($field, \strlen($identifier));
            if (\preg_match(self::SUPPLEMENTAL[$identifier], $content) !== 1) {
                throw self::fail('supplemental data must be S, 16D, 14D or Q with its data');
            }

            if (isset($values[$identifier])) {
                throw self::fail('repeats a supplemental data identifier');
            }

            $values[$identifier] = $content;
        }

        $expiry = isset($values['14D']) ? self::date(HibcDateFormat::Yyyymmdd, $values['14D']) : null;
        $quantity = isset($values['Q']) ? \max(0, \min(99999, (int) $values['Q'])) : null;

        return new HibcData(
            $data->primary,
            $data->labelerIdentificationCode,
            $data->productNumber,
            $data->unitOfMeasure,
            $data->lot,
            $data->serial ?? self::nonEmpty($values['S'] ?? ''),
            $data->expiryDate ?? $expiry,
            $data->expiryDateFormat ?? ($expiry instanceof DateTimeImmutable ? HibcDateFormat::Yyyymmdd : null),
            isset($values['16D']) ? self::date(HibcDateFormat::Yyyymmdd, $values['16D']) : null,
            $data->quantity ?? $quantity,
            self::nonEmpty($values['S'] ?? ''),
            $expiry,
            $quantity,
        );
    }

    /**
     * @return key-of<self::SUPPLEMENTAL>
     */
    private static function supplementalIdentifier(string $field): string
    {
        foreach (\array_keys(self::SUPPLEMENTAL) as $identifier) {
            if (\str_starts_with($field, $identifier)) {
                return $identifier;
            }
        }

        throw self::fail('supplemental data must be S, 16D, 14D or Q with its data');
    }

    /**
     * @return non-empty-string|null
     */
    private static function nonEmpty(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private static function fail(string $reason): InvalidUdiException
    {
        return new InvalidUdiException(\sprintf('HIBC UDI %s.', $reason));
    }
}
