<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;

/**
 * What the leading digits of a key (its GS1 Prefix) say about the number: restricted circulation, coupons, ISBN,
 * ISSN and so on. The allocation of a prefix to one GS1 Member Organisation is a registry fact that the General
 * Specifications do not carry, so every prefix that issues company numbers decodes as `MemberOrganisation`. A
 * well-formed prefix outside the table (`951`, `984`-`989`, and `977`-`999` for a GTIN-8) decodes as `Unknown` and is never refused.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 1.2.3.1, Tables 1-4 and 1-5
 * @see docs/sources/GS1.md "GS1 Prefix list"
 */
enum Gs1Prefix
{
    case MemberOrganisation;
    case RestrictedCirculation;
    case Coupon;
    case Isbn;
    case Issn;
    case Demonstration;
    case RefundReceipt;
    case Unused;
    case Unknown;

    /**
     * Decodes the leading digits of a number in the 13-digit key space (GTIN-13 and the keys that start with a GS1
     * Company Prefix): at least three digits. A run of fewer than seven zeros completes to either `0000000`
     * (restricted) or `0000001`-`0000099` (unused), so it decodes as `Unknown`.
     */
    public static function fromDigits(string $digits): self
    {
        if (\strspn($digits, Gs1Charsets::DIGITS) !== \strlen($digits) || \strlen($digits) < 3) {
            throw KeyRules::fail('GS1 Prefix', 'needs at least 3 leading digits');
        }

        $two = \substr($digits, 0, 2);
        $three = \substr($digits, 0, 3);

        return match (true) {
            \str_starts_with($digits, '0000000') => self::RestrictedCirculation,
            \strspn($digits, '0') === \strlen($digits) => self::Unknown,
            \strlen($digits) >= 6 && \str_starts_with($digits, '00000') => self::Unused,
            $two === '02', $two === '04', $two >= '20' && $two <= '29' => self::RestrictedCirculation,
            $three === '952' => self::Demonstration,
            $three === '977' => self::Issn,
            $three === '978', $three === '979' => self::Isbn,
            $three === '980' => self::RefundReceipt,
            $three >= '981' && $three <= '983', $two === '99' => self::Coupon,
            $three === '951', $three >= '984' && $three <= '989' => self::Unknown,
            default => self::MemberOrganisation,
        };
    }

    /**
     * Decodes the first three digits of a GTIN-8 (GS1-8 Prefix, Table 1-5).
     */
    public static function fromGtin8Digits(string $digits): self
    {
        if (\strspn($digits, Gs1Charsets::DIGITS) !== \strlen($digits) || \strlen($digits) < 3) {
            throw KeyRules::fail('GS1-8 Prefix', 'needs at least 3 leading digits');
        }

        $three = \substr($digits, 0, 3);

        return match (true) {
            $three <= '099', $three >= '200' && $three <= '299' => self::RestrictedCirculation,
            $three === '952' => self::Demonstration,
            $three >= '977' => self::Unknown,
            default => self::MemberOrganisation,
        };
    }
}
