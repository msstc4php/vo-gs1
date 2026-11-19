<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;

/**
 * @internal
 *
 * Shared checks of the identification keys. Messages name the key and the length or the reason, never the input.
 */
final class KeyRules
{
    private const int COMPANY_PREFIX_MIN = 3;

    private const int COMPANY_PREFIX_MAX = 12;

    public static function fail(string $subject, string $reason): InvalidIdentificationKeyException
    {
        return new InvalidIdentificationKeyException(\sprintf('%s %s.', $subject, $reason));
    }

    public static function digits(string $value, string $subject, int $length): void
    {
        if (\strlen($value) !== $length) {
            throw self::fail($subject, \sprintf('must have %d digits, got %d characters', $length, \strlen($value)));
        }

        if (\strspn($value, Gs1Charsets::DIGITS) !== $length) {
            throw self::fail($subject, 'must contain digits only');
        }
    }

    /**
     * Fixed-length numeric key whose last digit is the GS1 check digit.
     */
    public static function checkedDigits(string $value, string $subject, int $length): void
    {
        self::digits($value, $subject, $length);

        if (!Gs1CheckDigit::isValid($value)) {
            throw self::fail($subject, 'has a wrong check digit');
        }
    }

    /**
     * Variable-length text of `$charset`, 1 to `$max` characters, or the optional serial part of a key.
     */
    public static function text(string $value, string $subject, string $charset, int $max): void
    {
        $length = \strlen($value);
        if ($length < 1 || $length > $max) {
            throw self::fail($subject, \sprintf('must have 1 to %d characters, got %d', $max, $length));
        }

        if (\strspn($value, $charset) !== $length) {
            throw self::fail($subject, 'contains a character outside its GS1 character set');
        }
    }

    /**
     * A key that starts with the GS1 Company Prefix needs its first digits to decode the GS1 Prefix.
     */
    public static function startsWithPrefixDigits(string $value, string $subject): void
    {
        if (\strspn($value, Gs1Charsets::DIGITS) < self::COMPANY_PREFIX_MIN) {
            throw self::fail($subject, 'must start with the digits of a GS1 Company Prefix');
        }
    }

    /**
     * The digits before the first non-digit, the part of a key a GS1 Prefix can be read from.
     */
    public static function leadingDigits(string $value): string
    {
        return \substr($value, 0, \strspn($value, Gs1Charsets::DIGITS));
    }

    /**
     * Company prefix followed by the reference: the body of a key, without check digit.
     *
     * @param int|list<int> $bodyLengths allowed digit counts of the body
     */
    public static function body(string $subject, string $companyPrefix, string $reference, int|array $bodyLengths): string
    {
        self::assertCompanyPrefix($subject, $companyPrefix);

        $body = $companyPrefix . $reference;
        $lengths = (array) $bodyLengths;
        if (!\in_array(\strlen($body), $lengths, true)) {
            throw self::fail($subject, \sprintf('company prefix and reference must have %s digits in total, got %d', \implode(' or ', $lengths), \strlen($body)));
        }

        if (\strspn($body, Gs1Charsets::DIGITS) !== \strlen($body)) {
            throw self::fail($subject, 'company prefix and reference must contain digits only');
        }

        return $body;
    }

    /**
     * Company prefix and reference of a key with a free-form (non-numeric) reference.
     */
    public static function textBody(string $subject, string $companyPrefix, string $reference): string
    {
        self::assertCompanyPrefix($subject, $companyPrefix);

        return $companyPrefix . $reference;
    }

    private static function assertCompanyPrefix(string $subject, string $companyPrefix): void
    {
        $length = \strlen($companyPrefix);
        if ($length < self::COMPANY_PREFIX_MIN || $length > self::COMPANY_PREFIX_MAX
            || \strspn($companyPrefix, Gs1Charsets::DIGITS) !== $length) {
            throw self::fail($subject, \sprintf('company prefix must have %d to %d digits', self::COMPANY_PREFIX_MIN, self::COMPANY_PREFIX_MAX));
        }
    }
}
