<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;

/**
 * @internal
 *
 * The standard check digit of the fixed-length numeric GS1 keys (GTIN, GLN, SSCC, GSRN, GSIN, GDTI, GRAI, GCN): from
 * the right, weights 3 and 1 alternate; the check digit takes the sum up to the next multiple of ten. A body that is
 * not ASCII digits throws \LogicException.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 7.9.1, Table 7-8
 */
final class Gs1CheckDigit
{
    /**
     * @return numeric-string one digit
     */
    public static function compute(string $digits): string
    {
        if ($digits === '' || \strspn($digits, '0123456789') !== \strlen($digits)) {
            throw new LogicException('GS1 check digit input must be ASCII digits.');
        }

        $sum = 0;
        $weight = 3;
        for ($i = \strlen($digits) - 1; $i >= 0; $i--) {
            $sum += (\ord($digits[$i]) - 48) * $weight;
            $weight = 4 - $weight;
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    /**
     * Whether the last character of `$digits` is the check digit of the digits before it; those must be digits.
     */
    public static function isValid(string $digits): bool
    {
        return self::compute(\substr($digits, 0, -1)) === \substr($digits, -1);
    }
}
