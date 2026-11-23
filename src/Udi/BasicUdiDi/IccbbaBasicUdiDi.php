<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Internal\IsbtSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Iso7064Mod37;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The ICCBBA (ISBT 128) Basic UDI-DI `nnnnnppppppqqqqqbb`: "identical to the UDI-DI format with the exception that it
 * carries an additional two digits that form a mod 37-2 checksum" — FIN(P), Facility-defined Product Code, Product
 * Description Code (`00000` when the UDI-DIs use several) and the ISO/IEC 7064 MOD 37-2 checksum of the first 16.
 *
 * @see https://iccbba.org/wp-content/uploads/2025/08/1a7593_815cf8e500b9438a860dd8fae533b2b4.pdf ICCBBA ST-017 v1.0.1, Appendix 1 (example A9990ABC123T0480 → 08)
 * @see docs/sources/GS1.md "ICCBBA (ISBT 128)"
 */
final class IccbbaBasicUdiDi extends AbstractStringValueObject implements BasicUdiDi
{
    private const string PATTERN = '/^' . IsbtSyntax::PPIC . '([0-9]{2})$/D';

    #[Override]
    public function issuingAgency(): IssuingAgency
    {
        return IssuingAgency::Iccbba;
    }

    /**
     * @return non-empty-string
     */
    public function facilityIdentificationNumber(): string
    {
        return $this->parts()[1];
    }

    /**
     * @return non-empty-string
     */
    public function facilityProductCode(): string
    {
        return $this->parts()[2];
    }

    /**
     * @return non-empty-string
     */
    public function productDescriptionCode(): string
    {
        return $this->parts()[3];
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidUdiException('ICCBBA Basic UDI-DI must be a 16-character ISBT 128 UDI-DI and two checksum digits.');
        }

        if (Iso7064Mod37::compute(\substr($value, 0, 16)) !== \substr($value, 16)) {
            throw new InvalidUdiException('ICCBBA Basic UDI-DI has a wrong MOD 37-2 checksum.');
        }

        return $value;
    }

    /**
     * @return array{string, non-empty-string, non-empty-string, non-empty-string, non-empty-string}
     */
    private function parts(): array
    {
        if (\preg_match(self::PATTERN, $this->value, $match) !== 1) {
            throw new LogicException('ICCBBA Basic UDI-DI lost its parts.');
        }

        return $match;
    }
}
