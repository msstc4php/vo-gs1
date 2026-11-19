<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;

/**
 * @internal
 *
 * The GS1 check character pair of alphanumeric keys (GMN, MOD 1021,32): each character's position in CSET 82 is
 * weighted by the primes 2, 3, 5, … from the right; the sum modulo 1021 is split into two values of CSET 32. The body
 * is 1 to 23 characters, the longest GMN (AI 8013, X..25) without its pair.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 7.9.5, Tables 7-18 and 7-19
 */
final class GmnCheckCharacters
{
    private const string CSET_82 = '!"%&\'()*+,-./0123456789:;<=>?ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

    private const string CSET_32 = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /** @var list<positive-int> */
    private const array PRIMES = [2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83];

    /**
     * @return non-empty-string two characters of CSET 32
     */
    public static function compute(string $body): string
    {
        $length = \strlen($body);
        if ($length === 0 || $length > \count(self::PRIMES) || \strspn($body, self::CSET_82) !== $length) {
            throw new LogicException('GMN check character input must be 1 to 23 characters of GS1 CSET 82.');
        }

        $sum = 0;
        foreach (\str_split(\strrev($body)) as $position => $character) {
            $sum += \strcspn(self::CSET_82, $character) * self::PRIMES[$position];
        }

        $value = $sum % 1021;

        return self::CSET_32[\intdiv($value, 32)] . self::CSET_32[$value % 32];
    }
}
