<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Marking\EU;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaMod97;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The IFA Pharmacy Product Number: a 2-character PRA Code, the national product number and two check digits
 * (modulo 97 over the ASCII values, PRA Code included), `110375286414`. PRA Code `11` carries the German PZN, whose
 * own mod-11 check digit is verified too; other agencies' numbers are format only (0-9, A-Z, 11 to 28 characters).
 *
 * @see https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA_Spec_PPN_Code_Handelspackung_EN.pdf IFA, PPN-Code Specification for Retail Packaging, Version 3.06, sections 4.1.3, 4.2, 4.3, Appendix A
 * @see https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA-Info_Check_Digit_Calculations_PZN_PPN_UDI_EN.pdf IFA, Check Digit Calculations of PZN, PPN and Basic UDI-DI
 * @see docs/sources/GS1.md "EU: falsified medicines and tobacco"
 */
final class Ppn extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x: pRA Code 2, product number at most 24, check digits 2. */
    public const int MAX_LENGTH = 28;

    private const string PATTERN = '/^([0-9A-Z]{2})([0-9A-Z]{7,24})([0-9]{2})$/D';

    private const string GERMAN_PRA_CODE = '11';

    /** Section 4.3: "0 4150 12345678 2", the GS1 Germany prefix for the PZN, made 14 digits with a leading 0. */
    private const string NTIN_PREFIX = '04150';

    private const string PZN = '/^[0-9]{8}$/D';

    public static function fromParts(string $praCode, string $productNumber): self
    {
        $data = $praCode . $productNumber;
        if (\preg_match('/^[0-9A-Z]{9,26}$/D', $data) !== 1) {
            throw new InvalidMarkingCodeException('PPN parts must be 9 to 26 characters of 0-9 and A-Z.');
        }

        return new self($data . IfaMod97::compute($data));
    }

    /**
     * @return non-empty-string
     */
    public function praCode(): string
    {
        return $this->parts()[1];
    }

    /**
     * @return non-empty-string
     */
    public function productNumber(): string
    {
        return $this->parts()[2];
    }

    /**
     * The German PZN (PRA Code 11), else null.
     *
     * @return ?non-empty-string
     */
    public function pzn(): ?string
    {
        return $this->praCode() === self::GERMAN_PRA_CODE ? $this->productNumber() : null;
    }

    /**
     * The NTIN-DE of the same PZN (PRA Code 11), else null.
     */
    public function toNtin(): ?Gtin
    {
        $pzn = $this->pzn();
        if ($pzn === null) {
            return null;
        }

        return new Gtin(self::NTIN_PREFIX . $pzn . Gs1CheckDigit::compute(self::NTIN_PREFIX . $pzn));
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match(self::PATTERN, $value, $match) !== 1) {
            throw new InvalidMarkingCodeException('PPN must be a 2-character PRA Code, a 7 to 24 character product number of 0-9 and A-Z and two check digits.');
        }

        if (IfaMod97::compute($match[1] . $match[2]) !== $match[3]) {
            throw new InvalidMarkingCodeException('PPN has wrong check digits.');
        }

        if ($match[1] === self::GERMAN_PRA_CODE && !$this->isPzn($match[2])) {
            throw new InvalidMarkingCodeException('PPN with PRA Code 11 must carry an 8-digit PZN with a valid check digit.');
        }

        return $value;
    }

    /**
     * Mod 11 with weights 1 to 7; "If the remainder is the number 10, this digit sequence is not used as PZN.".
     */
    private function isPzn(string $digits): bool
    {
        if (\preg_match(self::PZN, $digits) !== 1) {
            return false;
        }

        $sum = 0;
        for ($position = 0; $position < 7; $position++) {
            $sum += (int) $digits[$position] * ($position + 1);
        }

        return $sum % 11 === (int) $digits[7];
    }

    /**
     * @return array{string, non-empty-string, non-empty-string, numeric-string}
     */
    private function parts(): array
    {
        if (\preg_match(self::PATTERN, $this->value, $match) !== 1) {
            throw new LogicException('PPN lost its parts.');
        }

        return $match;
    }
}
