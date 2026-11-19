<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Gs1\Internal\IsbtData;
use MSSTC4PHP\ValueObject\Gs1\Internal\IsbtSyntax;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * An ISBT 128 UDI of a medical device containing MPHO: the Processor Product Identification Code (`=/`, the UDI-DI),
 * the Donation Identification Number (`=` + FIN), the Product Divisions (`=,`, the serial number) and, optionally, the
 * expiration date (`=>`), the production date (`=}`) and the MPHO lot number (`&,1`). Accepted as the Compound Message
 * of a Data Matrix (`=+06000=/A9999XYZ100T0476=,000025…`) or as the human-readable concatenation of the data
 * structures; the value is the latter, so both forms of one UDI are equal. {@see toCompoundMessage()} gives the
 * former, with an unspecified sequence.
 *
 * The DIN flag characters are kept in the value but are not part of the DIN, which reading software "should ignore".
 *
 * @see https://iccbba.org/wp-content/uploads/2025/08/1a7593_815cf8e500b9438a860dd8fae533b2b4.pdf ICCBBA ST-017 v1.0.1
 * @see docs/sources/GS1.md "ICCBBA (ISBT 128)"
 */
final class IsbtUdi extends AbstractStringValueObject implements Udi
{
    /**
     * The 16 characters of the Processor Product Identification Code, without its data identifier.
     */
    #[Override]
    public function deviceIdentifier(): string
    {
        return $this->data()->deviceIdentifier;
    }

    #[Override]
    public function issuingAgency(): IssuingAgency
    {
        return IssuingAgency::Iccbba;
    }

    /**
     * FIN(P), the facility that assigned the Product Description Code.
     *
     * @return non-empty-string
     */
    public function facilityIdentificationNumber(): string
    {
        return $this->data()->facilityIdentificationNumber;
    }

    /**
     * @return non-empty-string
     */
    public function facilityProductCode(): string
    {
        return $this->data()->facilityProductCode;
    }

    /**
     * @return non-empty-string
     */
    public function productDescriptionCode(): string
    {
        return $this->data()->productDescriptionCode;
    }

    /**
     * The 13-character DIN, without the flag characters.
     *
     * @return non-empty-string
     */
    public function donationIdentificationNumber(): string
    {
        return $this->data()->donationIdentificationNumber;
    }

    /**
     * The Product Divisions code, the serial number of the device.
     *
     * @return non-empty-string
     */
    public function serial(): string
    {
        return $this->data()->productDivisions;
    }

    /**
     * The day "at the end of which the item expires", at 00:00 UTC.
     */
    public function expiryDate(): ?DateTimeImmutable
    {
        return $this->data()->expiryDate;
    }

    public function productionDate(): ?DateTimeImmutable
    {
        return $this->data()->productionDate;
    }

    /**
     * @return non-empty-string|null
     */
    public function lot(): ?string
    {
        return $this->data()->lot;
    }

    /**
     * `=+`, the number of data structures, `000` (unspecified sequence) and the data structures.
     *
     * @return non-empty-string
     */
    public function toCompoundMessage(): string
    {
        return \sprintf('=+%02d000', \count($this->data()->structures)) . $this->value;
    }

    #[Override]
    protected function parse(string $value): string
    {
        return \implode('', IsbtSyntax::parse($value)->structures);
    }

    private function data(): IsbtData
    {
        return IsbtSyntax::parse($this->value);
    }
}
