<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\I18n\CountryCode;
use MSSTC4PHP\ValueObject\I18n\CurrencyCode;

/**
 * @internal
 *
 * The GS1 Barcode Syntax Tests ("linters") that the dictionary names on AI components. Each implemented linter
 * follows its reference implementation; a linter in {@see NOT_ENFORCED} always passes (format only), and the reason
 * is recorded in docs/sources/GS1.md.
 *
 * @see https://github.com/gs1/gs1-syntax-dictionary `src/lint_*.c` at tag 2026-01-27
 * @see docs/sources/GS1.md "Linters"
 */
final class AiLinters
{
    /** @var non-empty-list<non-empty-string> */
    public const array IMPLEMENTED = [
        'csum', 'csumalpha', 'gcppos1', 'gcppos2', 'yymmd0', 'yymmdd', 'yyyymmdd', 'hh', 'mi', 'ss', 'hhmi',
        'iso3166', 'iso3166999', 'iso3166alpha2', 'iso4217', 'iso5218', 'zero', 'nonzero', 'nozeroprefix', 'yesno',
        'winding', 'pieceoftotal', 'hasnondigit', 'posinseqslash', 'latitude', 'longitude', 'hyphen', 'importeridx',
        'pcenc',
    ];

    /** @var non-empty-array<non-empty-string, non-empty-string> linter => why it is not enforced */
    public const array NOT_ENFORCED = [
        'mediatype' => 'AIDC media type code list, grows with ICCBBA and GS1 assignments',
        'packagetype' => 'package type code list, grows with UN/ECE Recommendation 21 and GS1 additions',
        'iban' => 'per-country IBAN structure is a later wave',
        'couponcode' => 'North American coupon structure is out of scope',
        'couponposoffer' => 'North American coupon structure is out of scope',
    ];

    /** `lint_gcppos1.c`: "Currently the shortest GS1 Company Prefix is four digits." */
    private const int GCP_MIN_LENGTH = 4;

    /**
     * ISO 4217 codes of funds, precious metals, testing and "no currency" that `lint_iso4217.c` accepts and the
     * vo-base currency table (a list of currencies) does not carry.
     *
     * @var non-empty-list<non-empty-string>
     */
    private const array ISO_4217_NON_CURRENCY = ['955', '956', '957', '958', '959', '960', '961', '962', '963', '964', '965', '994', '999'];

    private const string CSET_64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    /**
     * @throws LogicException for a linter name this class does not know
     */
    public static function passes(string $linter, string $data): bool
    {
        if (\array_key_exists($linter, self::NOT_ENFORCED)) {
            return true;
        }

        return match ($linter) {
            'csum' => Gs1CheckDigit::isValid($data),
            'csumalpha' => self::csumalpha($data),
            'gcppos1' => self::gcppos1($data),
            'gcppos2' => $data !== '' && self::gcppos1(\substr($data, 1)),
            'yymmd0' => self::yymmdd($data, true),
            'yymmdd' => self::yymmdd($data, false),
            'yyyymmdd' => Gs1Dates::isDate($data, false),
            'hh' => self::number($data, 2, 23),
            'mi', 'ss' => self::number($data, 2, 59),
            'hhmi' => \strlen($data) === 4 && self::number(\substr($data, 0, 2), 2, 23) && self::number(\substr($data, 2), 2, 59),
            'iso3166' => self::iso3166($data),
            'iso3166999' => $data === '999' || self::iso3166($data),
            'iso3166alpha2' => CountryCode::tryFrom($data) instanceof CountryCode,
            'iso4217' => self::iso4217($data),
            'iso5218' => \in_array($data, ['0', '1', '2', '9'], true),
            'zero' => $data !== '' && \strspn($data, '0') === \strlen($data),
            'nonzero' => self::digits($data) && \strspn($data, '0') !== \strlen($data),
            'nozeroprefix' => self::digits($data) && !\str_starts_with($data, '0'),
            'yesno' => $data === '0' || $data === '1',
            'winding' => \in_array($data, ['0', '1', '9'], true),
            'pieceoftotal' => self::pieceOfTotal($data),
            'hasnondigit' => \strspn($data, Gs1Charsets::DIGITS) !== \strlen($data),
            'posinseqslash' => self::positionInSequence($data),
            'latitude' => self::number($data, 10, 1800000000),
            'longitude' => self::number($data, 10, 3600000000),
            'hyphen' => $data !== '' && \strspn($data, '-') === \strlen($data),
            'importeridx' => \strlen($data) === 1 && \str_contains(self::CSET_64, $data),
            'pcenc' => self::percentEncoding($data),
            default => throw new LogicException('Unknown GS1 linter.'),
        };
    }

    /**
     * Base64url as AI (8030) uses it: CSET 64 with at most two trailing `=` pad characters.
     */
    public static function isCset64(string $data): bool
    {
        $body = \rtrim($data, '=');

        return \strlen($data) - \strlen($body) <= 2 && \strspn($body, self::CSET_64) === \strlen($body);
    }

    private static function digits(string $data): bool
    {
        return $data !== '' && \strspn($data, Gs1Charsets::DIGITS) === \strlen($data);
    }

    private static function number(string $data, int $length, int $max): bool
    {
        return \strlen($data) === $length && self::digits($data) && (int) $data <= $max;
    }

    private static function csumalpha(string $data): bool
    {
        $length = \strlen($data);
        if ($length < 2) {
            return false;
        }

        if ($length === 2) {
            return $data === '22';
        }

        $body = \substr($data, 0, -2);
        if ($length > 25 || \strspn($body, Gs1Charsets::CSET_82) !== $length - 2) {
            return false;
        }

        return GmnCheckCharacters::compute($body) === \substr($data, -2);
    }

    private static function gcppos1(string $data): bool
    {
        return \strlen($data) >= self::GCP_MIN_LENGTH && \strspn($data, Gs1Charsets::DIGITS, 0, self::GCP_MIN_LENGTH) === self::GCP_MIN_LENGTH;
    }

    /**
     * Month and day only: the century is a reading of the date, not part of its validity, so a leap year is any `YY`
     * divisible by four (`00` included) and the result never depends on the clock.
     */
    private static function yymmdd(string $data, bool $dayZero): bool
    {
        if (\strlen($data) !== 6 || !self::digits($data)) {
            return false;
        }

        $year = (int) \substr($data, 0, 2) % 4 === 0 ? 2000 : 2001;

        return Gs1Dates::isDate(\sprintf('%04d', $year) . \substr($data, 2), $dayZero);
    }

    private static function iso3166(string $data): bool
    {
        if (\strlen($data) !== 3 || !self::digits($data)) {
            return false;
        }

        try {
            CountryCode::fromNumeric($data);
        } catch (InvalidValueObjectException) {
            return false;
        }

        return true;
    }

    private static function iso4217(string $data): bool
    {
        if (\strlen($data) !== 3 || !self::digits($data)) {
            return false;
        }

        if (\in_array($data, self::ISO_4217_NON_CURRENCY, true)) {
            return true;
        }

        try {
            CurrencyCode::fromNumeric($data);
        } catch (InvalidValueObjectException) {
            return false;
        }

        return true;
    }

    /**
     * Piece number and total of equal width, both non-zero, the piece not above the total.
     */
    private static function pieceOfTotal(string $data): bool
    {
        $length = \strlen($data);
        if (!self::digits($data) || $length % 2 !== 0) {
            return false;
        }

        $piece = \substr($data, 0, $length >> 1);
        $total = \substr($data, $length >> 1);

        return \ltrim($piece, '0') !== '' && \ltrim($total, '0') !== '' && \strcmp($piece, $total) <= 0;
    }

    /**
     * `<pos>/<end>`, both without leading zero, the position not above the end.
     */
    private static function positionInSequence(string $data): bool
    {
        $parts = \explode('/', $data);
        if (\count($parts) !== 2) {
            return false;
        }

        [$position, $end] = $parts;
        if (!self::digits($position) || !self::digits($end) || $position[0] === '0' || $end[0] === '0') {
            return false;
        }

        return \strlen($position) < \strlen($end) || (\strlen($position) === \strlen($end) && \strcmp($position, $end) <= 0);
    }

    private static function percentEncoding(string $data): bool
    {
        $offset = 0;
        while (($position = \strpos($data, '%', $offset)) !== false) {
            if (\strlen($data) - $position < 3 || !\ctype_xdigit(\substr($data, $position + 1, 2))) {
                return false;
            }

            $offset = $position + 3;
        }

        return true;
    }
}
