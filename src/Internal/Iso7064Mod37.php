<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;

/**
 * @internal
 *
 * The ISO/IEC 7064 MOD 37-2 checksum of an ICCBBA Basic UDI-DI, written as two digits `00`-`36`: each character's
 * value (`0`-`9` = 0-9, `A`-`Z` = 10-35) times 2 to the power of its position from the right (1 for the last), summed,
 * then `(38 - sum mod 37) mod 37`
 *
 * @see https://iccbba.org/wp-content/uploads/2025/08/1a7593_815cf8e500b9438a860dd8fae533b2b4.pdf ICCBBA ST-017 v1.0.1, Appendix 1, "Calculating the Basic UDI-DI Checksum"
 */
final class Iso7064Mod37
{
    private const string VALUES = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @return non-empty-string two digits
     */
    public static function compute(string $data): string
    {
        if ($data === '' || \strspn($data, self::VALUES) !== \strlen($data)) {
            throw new LogicException('MOD 37-2 input must be digits and capital letters.');
        }

        // Horner's scheme gives the same sum modulo 37 without the powers of two.
        $sum = 0;
        foreach (\str_split($data) as $character) {
            $sum = ($sum + \strcspn(self::VALUES, $character)) * 2 % 37;
        }

        return \sprintf('%02d', (38 - $sum) % 37);
    }
}
