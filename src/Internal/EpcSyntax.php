<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementStringBuilder;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcScheme;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcTagEncoding;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidEpcUriException;

/**
 * @internal
 *
 * The EPC pure identity URI grammar per scheme, its correspondence to GS1 element strings, and the EPC Tag URI
 *
 * @see https://ref.gs1.org/standards/tds/ EPC Tag Data Standard 2.3, sections 5, 6.3, 7, 10, 12 and 14.6
 */
final class EpcSyntax
{
    // No EPC comes near it: the longest component, a GIAI or GINC reference, has 30 characters, 90 percent-escaped.
    public const int MAX_LENGTH = 1024;

    private const string GS3A3 = '(?:[0-9A-Za-z!\'()*+,\-.:;=_]|%[0-9A-F]{2})+';

    private const string CP_REF = '(?:[0-9A-Z\-]|%2F|%23)+';

    private const string NUMERIC = '(?:0|[1-9][0-9]*)';

    // TDS 6.3.17: CAGECodeOrDODAACChar = Digit / %x41-48 / %x4A-4E / %x50-5A ; 0-9 A-H J-N P-Z
    private const string CAGE_OR_DODAAC = '[0-9A-HJ-NP-Z]{5,6}';

    private const string ADI_CHAR = '(?:[0-9A-Z\-]|%2F)';

    private const int COMPANY_PREFIX_MIN = 6;

    private const int COMPANY_PREFIX_MAX = 12;

    /** Partition 0 to 6: the bits of the GIAI-96 individual asset reference (TDS Table "GIAI-96 Partition Table"). */
    private const array GIAI_96_BITS = [42, 45, 48, 52, 55, 58, 62];

    /**
     * Upper-cases the hex digits of percent escapes, the one normalisation TDS section 6.1 names.
     */
    public static function canonical(string $value): string
    {
        if (\strlen($value) > self::MAX_LENGTH) {
            throw self::fail(\sprintf('EPC URI must have at most %d characters.', self::MAX_LENGTH));
        }

        return (string) \preg_replace_callback('/%[0-9A-Fa-f]{2}/', static fn (array $match): string => \strtoupper($match[0]), $value);
    }

    /**
     * @return array{EpcScheme, string} the scheme and the body after `urn:epc:id:<scheme>:`
     */
    public static function pure(string $canonical): array
    {
        if (\preg_match('/\Aurn:epc:id:([a-z]+):(.*)\z/', $canonical, $match) !== 1 || !EpcScheme::tryFrom($match[1]) instanceof EpcScheme) {
            throw self::fail('EPC pure identity URI must start with urn:epc:id: and name a known scheme.');
        }

        return [EpcScheme::from($match[1]), $match[2]];
    }

    /**
     * @return non-empty-list<string> the dot-separated components, still percent-escaped
     */
    public static function components(EpcScheme $scheme, string $body): array
    {
        [$pattern, $digits] = self::grammar($scheme);
        if (\preg_match('/\A' . $pattern . '\z/', $body, $match) !== 1 || !isset($match[1])) {
            throw self::grammarFailure();
        }

        $components = [$match[1], ...\array_values(\array_slice($match, 2))];
        if ($digits !== null && \strlen($components[0]) + \strlen($components[1] ?? '') !== $digits) {
            throw self::grammarFailure();
        }

        $prefix = \strlen($components[0]);
        if ($scheme->hasElementString() && ($prefix < self::COMPANY_PREFIX_MIN || $prefix > self::COMPANY_PREFIX_MAX)) {
            throw self::fail('EPC pure identity URI company prefix must have 6 to 12 digits.');
        }

        return $components;
    }

    /**
     * The element string of TDS section 7, null for the schemes without one.
     *
     * @param non-empty-list<string> $c
     */
    public static function elementString(EpcScheme $scheme, array $c): ?ElementString
    {
        $elements = match ($scheme) {
            EpcScheme::Sgtin => ['01' => self::gtin($c), '21' => \rawurldecode($c[2])],
            EpcScheme::Upui => ['01' => self::gtin($c), '235' => \rawurldecode($c[2])],
            EpcScheme::Itip => ['8006' => self::gtin($c) . $c[2] . $c[3], '21' => \rawurldecode($c[4])],
            EpcScheme::Sscc => ['00' => self::checked($c[1][0] . $c[0] . \substr($c[1], 1))],
            EpcScheme::Sgln => $c[2] === '0' ? ['414' => self::checked($c[0] . $c[1])] : ['414' => self::checked($c[0] . $c[1]), '254' => \rawurldecode($c[2])],
            EpcScheme::Grai => ['8003' => '0' . self::checked($c[0] . $c[1]) . \rawurldecode($c[2])],
            EpcScheme::Giai => ['8004' => $c[0] . \rawurldecode($c[1])],
            EpcScheme::Gsrn => ['8018' => self::checked($c[0] . $c[1])],
            EpcScheme::Gsrnp => ['8017' => self::checked($c[0] . $c[1])],
            EpcScheme::Gdti => ['253' => self::checked($c[0] . $c[1]) . \rawurldecode($c[2])],
            EpcScheme::Cpi => ['8010' => $c[0] . \rawurldecode($c[1]), '8011' => $c[2]],
            EpcScheme::Sgcn => ['255' => self::checked($c[0] . $c[1]) . $c[2]],
            EpcScheme::Ginc => ['401' => $c[0] . \rawurldecode($c[1])],
            EpcScheme::Gsin => ['402' => self::checked($c[0] . $c[1])],
            EpcScheme::Pgln => ['417' => self::checked($c[0] . $c[1])],
            default => null,
        };

        if ($elements === null) {
            return null;
        }

        try {
            $builder = new ElementStringBuilder();
            foreach ($elements as $ai => $value) {
                $builder = $builder->with((string) $ai, $value);
            }

            return $builder->build();
        } catch (InvalidElementStringException $exception) {
            throw new InvalidEpcUriException('EPC pure identity URI does not correspond to a valid GS1 element string: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
        }
    }

    /**
     * The body of the pure identity URI of `$elementString` (TDS section 7), splitting the keys at
     * `$companyPrefixLength` digits; AIs the scheme does not use are ignored.
     */
    public static function body(ElementString $elementString, EpcScheme $scheme, int $companyPrefixLength): string
    {
        if (!$scheme->hasElementString()) {
            throw self::fail('EPC scheme has no GS1 element string form.');
        }

        if ($companyPrefixLength < self::COMPANY_PREFIX_MIN || $companyPrefixLength > self::COMPANY_PREFIX_MAX) {
            throw self::fail('EPC company prefix length must be 6 to 12.');
        }

        $l = $companyPrefixLength;
        $need = static fn (string $ai): string => $elementString->get($ai) ?? throw self::fail('EPC needs the AIs of its scheme in the element string.');
        $serialised = static function (string $ai, int $keyLength) use ($need): string {
            $value = $need($ai);
            if (\strlen($value) <= $keyLength) {
                throw self::fail('EPC needs a serial number in the key AI of its scheme.');
            }

            return $value;
        };
        $gtin = static fn (string $g): string => \substr($g, 1, $l) . '.' . $g[0] . \substr($g, 1 + $l, 12 - $l);
        $text = static fn (string $value): string => self::escape($value, '0-9A-Za-z!\'()*+,\-.:;=_');

        return match ($scheme) {
            EpcScheme::Sgtin => $gtin($need('01')) . '.' . $text($need('21')),
            EpcScheme::Upui => $gtin($need('01')) . '.' . $text($need('235')),
            EpcScheme::Itip => $gtin($need('8006')) . '.' . \substr($need('8006'), 14, 2) . '.' . \substr($need('8006'), 16) . '.' . $text($need('21')),
            EpcScheme::Sscc => \substr($need('00'), 1, $l) . '.' . $need('00')[0] . \substr($need('00'), 1 + $l, 16 - $l),
            EpcScheme::Sgln => \substr($need('414'), 0, $l) . '.' . \substr($need('414'), $l, 12 - $l) . '.' . $text($elementString->get('254') ?? '0'),
            EpcScheme::Grai => \substr($serialised('8003', 14), 1, $l) . '.' . \substr($need('8003'), 1 + $l, 12 - $l) . '.' . $text(\substr($need('8003'), 14)),
            EpcScheme::Giai => \substr($need('8004'), 0, $l) . '.' . $text(\substr($need('8004'), $l)),
            EpcScheme::Gsrn => \substr($need('8018'), 0, $l) . '.' . \substr($need('8018'), $l, 17 - $l),
            EpcScheme::Gsrnp => \substr($need('8017'), 0, $l) . '.' . \substr($need('8017'), $l, 17 - $l),
            EpcScheme::Gdti => \substr($serialised('253', 13), 0, $l) . '.' . \substr($need('253'), $l, 12 - $l) . '.' . $text(\substr($need('253'), 13)),
            EpcScheme::Cpi => \substr($need('8010'), 0, $l) . '.' . self::escape(\substr($need('8010'), $l), '0-9A-Z\-') . '.' . $need('8011'),
            EpcScheme::Sgcn => \substr($serialised('255', 13), 0, $l) . '.' . \substr($need('255'), $l, 12 - $l) . '.' . \substr($need('255'), 13),
            EpcScheme::Ginc => \substr($need('401'), 0, $l) . '.' . $text(\substr($need('401'), $l)),
            EpcScheme::Gsin => \substr($need('402'), 0, $l) . '.' . \substr($need('402'), $l, 16 - $l),
            default => \substr($need('417'), 0, $l) . '.' . \substr($need('417'), $l, 12 - $l),
        };
    }

    /**
     * @return array{EpcTagEncoding, int<0, 63>|null, string} the encoding, the filter and the body of the pure identity URI
     */
    public static function tag(string $canonical): array
    {
        if (\preg_match('/\Aurn:epc:tag:((?:\[[^\]]*\])+:)?([a-z0-9]+-[a-z0-9]+):(.*)\z/', $canonical, $match) !== 1) {
            throw self::fail('EPC Tag URI must start with urn:epc:tag: and name an encoding.');
        }

        if ($match[1] !== '') {
            self::controlFields($match[1]);
        }

        $encoding = EpcTagEncoding::tryFrom($match[2]) ?? throw self::fail('EPC Tag URI names an unknown encoding.');
        if (!$encoding->hasFilter()) {
            return [$encoding, null, $match[3]];
        }

        $body = $match[3];
        // A body that is already a whole EPC of the scheme means the filter was left out.
        $reject = static fn (string $message): InvalidEpcUriException => self::fail(self::isBody($encoding->scheme(), $body) ? 'EPC Tag URI has no filter value.' : $message);
        if (\preg_match('/\A([0-9]+)\.(.*)\z/', $body, $filter) !== 1) {
            throw $reject('EPC Tag URI must have a filter value before the EPC.');
        }

        if ($filter[1] !== '0' && \str_starts_with($filter[1], '0')) {
            throw $reject('EPC Tag URI filter value must be a number without leading zeros.');
        }

        $limit = 1 << (int) $encoding->filterBits();
        // Every filter field is at most 6 bits wide, so two digits; a longer run must not reach the int cast.
        if (\strlen($filter[1]) > 2 || (int) $filter[1] >= $limit) {
            throw $reject('EPC Tag URI filter value is above the limit of its encoding.');
        }

        $value = (int) $filter[1];
        \assert($value >= 0, 'A run of digits casts to a non-negative int.');

        return [$encoding, $value, $filter[2]];
    }

    /**
     * The limits Table 12-2 and the coding tables of section 14.6 put on the fields of an encoding.
     *
     * @param non-empty-list<string> $c
     */
    public static function checkEncoding(EpcTagEncoding $encoding, array $c): void
    {
        $partition = self::COMPANY_PREFIX_MAX - \strlen($c[0]);
        match ($encoding) {
            EpcTagEncoding::Sgtin96, EpcTagEncoding::Grai96 => self::below($c[2], 38),
            EpcTagEncoding::Sgln96, EpcTagEncoding::Gdti96 => self::below($c[2], 41),
            EpcTagEncoding::Itip110 => self::below($c[4], 38),
            EpcTagEncoding::Gdti113 => self::digits($c[2]),
            EpcTagEncoding::Giai96 => [self::maxDigits($c[1], 13 + $partition), self::below($c[1], self::GIAI_96_BITS[$partition])],
            // Within 3 + P digits the reference is always below its 11 to 31 bits.
            EpcTagEncoding::Cpi96 => [self::maxDigits($c[1], 3 + $partition), self::below($c[2], 31)],
            EpcTagEncoding::Gid96 => [self::below($c[0], 28), self::below($c[1], 24), self::below($c[2], 36)],
            EpcTagEncoding::Usdod96 => self::below($c[1], 36),
            EpcTagEncoding::AdiVar => [self::maxCharacters($c[1], 32), self::maxCharacters($c[2], 30)],
            default => null,
        };
    }

    public static function fail(string $message): InvalidEpcUriException
    {
        return new InvalidEpcUriException($message);
    }

    /**
     * @return array{non-empty-string, int|null} the body pattern and the digit count of its first two components
     */
    private static function grammar(EpcScheme $scheme): array
    {
        $cp = '([0-9]+)';
        $serialised = $cp . '\.([0-9]*)\.(' . self::GS3A3 . ')';

        return match ($scheme) {
            EpcScheme::Sgtin, EpcScheme::Upui => [$cp . '\.([0-9]+)\.(' . self::GS3A3 . ')', 13],
            EpcScheme::Sscc, EpcScheme::Gsrn, EpcScheme::Gsrnp => [$cp . '\.([0-9]+)', 17],
            EpcScheme::Gsin => [$cp . '\.([0-9]+)', 16],
            EpcScheme::Sgln, EpcScheme::Grai, EpcScheme::Gdti => [$serialised, 12],
            EpcScheme::Sgcn => [$cp . '\.([0-9]*)\.([0-9]+)', 12],
            EpcScheme::Pgln => [$cp . '\.([0-9]*)', 12],
            EpcScheme::Giai, EpcScheme::Ginc => [$cp . '\.(' . self::GS3A3 . ')', null],
            EpcScheme::Cpi => [$cp . '\.(' . self::CP_REF . ')\.(' . self::NUMERIC . ')', null],
            EpcScheme::Itip => [$cp . '\.([0-9]+)\.([0-9]{2})\.([0-9]{2})\.(' . self::GS3A3 . ')', 13],
            EpcScheme::Gid => ['(' . self::NUMERIC . ')\.(' . self::NUMERIC . ')\.(' . self::NUMERIC . ')', null],
            EpcScheme::Usdod => ['(' . self::CAGE_OR_DODAAC . ')\.(' . self::NUMERIC . ')', null],
            EpcScheme::Adi => ['(' . self::CAGE_OR_DODAAC . ')\.(' . self::ADI_CHAR . '*)\.((?:%23)?' . self::ADI_CHAR . '+)', null],
            // TDS 6.3.19's grammar leaves I and O out of OwnerCodeChar, its prose says "three capital letters" and
            // registered owner codes use both (OOLU), so the prose wins: never refuse a valid container code.
            EpcScheme::Bic => ['([A-Z]{3}[JUZ][0-9]{7})', null],
            EpcScheme::Imovn => ['([0-9]{7})', null],
        };
    }

    private static function isBody(EpcScheme $scheme, string $body): bool
    {
        try {
            self::components($scheme, $body);

            return true;
        } catch (InvalidEpcUriException) {
            return false;
        }
    }

    private static function grammarFailure(): InvalidEpcUriException
    {
        return self::fail('EPC pure identity URI does not match the grammar of its scheme.');
    }

    /**
     * The GTIN-14 of the first two components: indicator digit, company prefix, item reference, check digit.
     *
     * @param non-empty-list<string> $c
     */
    private static function gtin(array $c): string
    {
        return self::checked($c[1][0] . $c[0] . \substr($c[1], 1));
    }

    private static function checked(string $digits): string
    {
        return $digits . Gs1CheckDigit::compute($digits);
    }

    private static function escape(string $value, string $allowed): string
    {
        return (string) \preg_replace_callback('/[^' . $allowed . ']/', static fn (array $match): string => \sprintf('%%%02X', \ord($match[0])), $value);
    }

    private static function controlFields(string $controls): void
    {
        \preg_match_all('/\[(att|umi|xpc)=(?:0|1|x[0-9A-F]+)\]/', $controls, $fields);
        $names = $fields[1];
        $ordered = $names;
        \sort($ordered);
        if (\implode('', $fields[0]) . ':' !== $controls || $names !== $ordered || \count(\array_unique($names)) !== \count($names)) {
            throw self::fail('EPC Tag URI control fields must be att, umi, xpc, each at most once and in that order.');
        }
    }

    private static function numeric(string $value): void
    {
        if (\preg_match('/\A' . self::NUMERIC . '\z/', $value) !== 1) {
            throw self::fail('EPC Tag URI field must be a number without leading zeros.');
        }
    }

    private static function digits(string $value): true
    {
        if (\strspn($value, Gs1Charsets::DIGITS) !== \strlen($value)) {
            throw self::fail('EPC Tag URI field must be digits.');
        }

        return true;
    }

    private static function below(string $value, int $bits): true
    {
        self::numeric($value);
        $limit = (string) (1 << $bits);
        if (\strlen($value) > \strlen($limit) || (\strlen($value) === \strlen($limit) && \strcmp($value, $limit) >= 0)) {
            throw self::fail('EPC Tag URI field is above the limit of its encoding.');
        }

        return true;
    }

    private static function maxDigits(string $value, int $digits): true
    {
        self::numeric($value);
        if (\strlen($value) > $digits) {
            throw self::fail('EPC Tag URI field has more digits than its encoding and partition allow.');
        }

        return true;
    }

    private static function maxCharacters(string $value, int $characters): true
    {
        if (\strlen((string) \preg_replace('/%[0-9A-F]{2}/', '_', $value)) > $characters) {
            throw self::fail('EPC Tag URI field has more characters than its encoding allows.');
        }

        return true;
    }
}
