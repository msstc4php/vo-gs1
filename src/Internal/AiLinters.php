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
 * is recorded in docs/sources/GS1.md. The linters in {@see REPORTED} follow the GS1 Barcode Syntax Engine and are
 * reported by {@see passesReported()} only.
 *
 * Re-implements linters of the GS1 Barcode Syntax Dictionary and Engine (Apache License 2.0, Copyright GS1 AISBL).
 *
 * @see https://github.com/gs1/gs1-syntax-dictionary `src/lint_*.c` at tag 2026-01-27
 * @see https://github.com/gs1/gs1-syntax-engine/tree/58c1dc85d9ff4eb9368293bcf5264a9fe70bfc72/src/c-lib/syntax `lint_iban.c`, `lint_mediatype.c`
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "Linters"
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
        'mediatype' => 'AIDC media type code list, grows with ICCBBA and GS1 assignments; reported by passesReported(), refusal waits for 2.0',
        'packagetype' => 'package type code list, grows with UN/ECE Recommendation 21 and GS1 additions',
        'iban' => 'lint_iban.c refuses more than 1.x accepts; reported by passesReported(), refusal waits for 2.0',
        'couponcode' => 'North American coupon structure is out of scope',
        'couponposoffer' => 'North American coupon structure is out of scope',
    ];

    /**
     * Not-enforced linters whose reference implementation is checked for {@see passesReported()}: the engine refuses
     * more than this package may in 1.x, so the result is a report, never a refusal.
     *
     * @var non-empty-list<'iban'|'mediatype'>
     */
    public const array REPORTED = ['iban', 'mediatype'];

    /** `lint_iban.c`: "No clear minimum length; sufficient for check characters" (refused when not longer). */
    private const int IBAN_MIN_LENGTH = 10;

    /** `lint_iban.c`: "Per specification." */
    private const int IBAN_MAX_LENGTH = 34;

    private const string IBAN_CHARACTERS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    private const int IBAN_MODULUS = 97;

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
            // GenSpecs 3.9.11 allows the single digit "0", which lint_nozeroprefix.c refuses.
            'nozeroprefix' => self::digits($data) && ($data === '0' || !\str_starts_with($data, '0')),
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
     * The reference implementation of a {@see REPORTED} linter (`lint_iban.c`, `lint_mediatype.c` of the GS1 Barcode
     * Syntax Engine); true for any other linter.
     */
    public static function passesReported(string $linter, string $data): bool
    {
        return match ($linter) {
            'iban' => self::iban($data),
            'mediatype' => self::mediatype($data),
            default => true,
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

    /**
     * `lint_iban.c`: an ISO 3166 alpha-2 country, 11 to 34 characters of `0-9A-Z`, ISO 7064 mod 97-10 remainder 1 with
     * the first four characters moved to the end (check digits are not required to be digits).
     */
    private static function iban(string $data): bool
    {
        $length = \strlen($data);
        if ($length <= self::IBAN_MIN_LENGTH || $length > self::IBAN_MAX_LENGTH || \strspn($data, self::IBAN_CHARACTERS) !== $length
            || !CountryCode::tryFrom(\substr($data, 0, 2)) instanceof CountryCode) {
            return false;
        }

        $remainder = 0;
        foreach (\str_split(\substr($data, 4) . \substr($data, 0, 4)) as $character) {
            $value = \ord($character) - (\ctype_digit($character) ? \ord('0') : \ord('A') - 10);
            $remainder = ($remainder * ($value < 10 ? 10 : 100) + $value) % self::IBAN_MODULUS;
        }

        return $remainder === 1;
    }

    /**
     * `lint_mediatype.c`, GenSpecs 26.0 Table 3-11: "01 to 10 Current ICCBBA assignment" and "80 to 99 Reserved for
     * local or national use" pass; "00 Not used" and the reserved 11 to 79 fail.
     */
    private static function mediatype(string $data): bool
    {
        if (\strlen($data) !== 2 || !self::digits($data)) {
            return false;
        }

        $value = \intval($data);

        return ($value >= 1 && $value <= 10) || $value >= 80;
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
