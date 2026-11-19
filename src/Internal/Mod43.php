<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;

/**
 * @internal
 *
 * The HIBC LIC Modulo 43 check character: the sum of the Table B1 values of every character, modulo 43, mapped back
 * through the same table. The result may be a space (value 38).
 *
 * @see https://www.hibcc.org/wp-content/uploads/SLS-2.6-Final.pdf ANSI/HIBC 2.6-2016 (R2026), Appendix B, Table B1
 */
final class Mod43
{
    private const string TABLE_B1 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%';

    /**
     * @return non-empty-string one character of Table B1
     */
    public static function compute(string $data): string
    {
        if ($data === '' || \strspn($data, self::TABLE_B1) !== \strlen($data)) {
            throw new LogicException('Mod 43 input must be characters of HIBCC Table B1.');
        }

        $sum = 0;
        foreach (\str_split($data) as $character) {
            $sum += \strcspn(self::TABLE_B1, $character);
        }

        return self::TABLE_B1[$sum % 43];
    }
}
