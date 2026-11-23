<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaMod97;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The IFA Basic UDI-DI: the Issuing Agency Code `PP`, the 5-character manufacturer code (the IFA supplier number), a
 * Device Group Code of 1 to 16 characters of `0-9`, `A-Z` and `.`, and two check digits (modulo 97 over the ASCII
 * values, `PP` included): `PP12345ABCD.12345678.9004`. The manufacturer code is accepted alphanumeric: IFA's data
 * type is "A/Num", and "at present, exclusively numeric manufacturer codes are assigned".
 *
 * @see https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Spec_UDI_Code_EN.pdf IFA, Coding System for medical devices, Version 2.5 (1 April 2026), section 3.5, Figure 9
 * @see https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Check_Digit_Calculations_PZN_PPN_UDI_EN.pdf IFA, Check Digit Calculations of PZN, PPN and Basic UDI-DI
 * @see docs/sources/GS1.md "IFA Basic UDI-DI"
 */
final class IfaBasicUdiDi extends AbstractStringValueObject implements BasicUdiDi
{
    private const string PATTERN = '/^PP([0-9A-Z]{5})([0-9A-Z.]{1,16})([0-9]{2})$/D';

    #[Override]
    public function issuingAgency(): IssuingAgency
    {
        return IssuingAgency::Ifa;
    }

    /**
     * @return non-empty-string
     */
    public function manufacturerCode(): string
    {
        return $this->parts()[1];
    }

    /**
     * @return non-empty-string
     */
    public function deviceGroupCode(): string
    {
        return $this->parts()[2];
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidUdiException('IFA Basic UDI-DI must be "PP", a 5-character manufacturer code, a 1 to 16 character device group code and two check digits.');
        }

        if (IfaMod97::compute(\substr($value, 0, -2)) !== \substr($value, -2)) {
            throw new InvalidUdiException('IFA Basic UDI-DI has wrong check digits.');
        }

        return $value;
    }

    /**
     * @return array{string, non-empty-string, non-empty-string, non-empty-string}
     */
    private function parts(): array
    {
        if (\preg_match(self::PATTERN, $this->value, $match) !== 1) {
            throw new LogicException('IFA Basic UDI-DI lost its parts.');
        }

        return $match;
    }
}
