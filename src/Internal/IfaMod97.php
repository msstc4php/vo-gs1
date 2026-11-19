<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;

/**
 * @internal
 *
 * The IFA check digits of the PPN and the Basic UDI-DI: the ASCII value of each character, weighted from the left with
 * 2, 3, 4, …, summed modulo 97 and written as two digits
 *
 * @see https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Check_Digit_Calculations_PZN_PPN_UDI_EN.pdf IFA, Check Digit Calculations of PZN, PPN and Basic UDI-DI (26 January 2024)
 */
final class IfaMod97
{
    /**
     * @return non-empty-string two digits
     */
    public static function compute(string $data): string
    {
        if ($data === '' || \preg_match('/[^\x00-\x7F]/', $data) === 1) {
            throw new LogicException('IFA check digit input must be ASCII.');
        }

        $sum = 0;
        foreach (\str_split($data) as $position => $character) {
            $sum += \ord($character) * ($position + 2);
        }

        return \sprintf('%02d', $sum % 97);
    }
}
