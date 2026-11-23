<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Gs1\Internal\HibcData;
use MSSTC4PHP\ValueObject\Gs1\Internal\HibcSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Mod43;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * An HIBC LIC UDI: the primary data structure (the UDI-DI: "+", Labeler Identification Code, product number, unit of
 * measure, Mod 43 check character), alone or with its secondary data (lot or serial number, expiry date) and
 * supplemental data concatenated in one symbol: `+A123BJC5D6E71/$$52001510X3C`. Primary and secondary data printed as
 * two symbols are joined by {@see fromSymbols()}, which checks the link character; the value is then the concatenated
 * form, so both labels of one item give equal values.
 *
 * Two-digit years are read as 20YY; a month-only expiry (MMYY) is the last day of that month; dates are UTC, with the
 * hour where the format has one. {@see expiryDateFormat()} tells which format the label used.
 *
 * @see https://www.hibcc.org/wp-content/uploads/SLS-2.6-Final.pdf ANSI/HIBC 2.6-2016 (R2026), sections 2.1 to 2.3, Appendices B, E and F
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "HIBCC"
 */
final class HibcUdi extends AbstractStringValueObject implements Udi
{
    /** Maximum characters of the stored value, frozen for 1.x. */
    public const int MAX_LENGTH = HibcSyntax::MAX_LENGTH;

    /**
     * Joins a primary symbol (`+A123BJC5D6E71G`) and its secondary symbol (`+$$52001510X3GD`, whose link character `G`
     * is the primary check character) into the concatenated form.
     */
    public static function fromSymbols(string $primary, string $secondary): self
    {
        return new self(HibcSyntax::concatenate($primary, $secondary));
    }

    /**
     * The primary data structure with its own check character.
     */
    #[Override]
    public function deviceIdentifier(): string
    {
        $primary = $this->data()->primary;

        return $primary . Mod43::compute($primary);
    }

    #[Override]
    public function issuingAgency(): IssuingAgency
    {
        return IssuingAgency::Hibcc;
    }

    /**
     * @return non-empty-string
     */
    public function labelerIdentificationCode(): string
    {
        return $this->data()->labelerIdentificationCode;
    }

    /**
     * @return non-empty-string
     */
    public function productNumber(): string
    {
        return $this->data()->productNumber;
    }

    /**
     * 0 is the unit of use, 1 to 8 higher packaging levels, 9 a variable quantity container.
     *
     * @return int<0, 9>
     */
    public function unitOfMeasure(): int
    {
        return $this->data()->unitOfMeasure;
    }

    /**
     * @return non-empty-string|null
     */
    #[Override]
    public function lot(): ?string
    {
        return $this->data()->lot;
    }

    /**
     * The serial number of the secondary data (`$+`, `$$+`), else of the supplemental data identifier `S`. When both are
     * present HIBC does not say which one wins: this returns the secondary data's, {@see supplementalSerial()} the
     * other.
     *
     * @return non-empty-string|null
     */
    #[Override]
    public function serial(): ?string
    {
        return $this->data()->serial;
    }

    /**
     * The supplemental data identifier `S`.
     *
     * @return non-empty-string|null
     */
    public function supplementalSerial(): ?string
    {
        return $this->data()->supplementalSerial;
    }

    /**
     * The expiry date of the secondary data, else of the supplemental data identifier `14D`. When both are present
     * HIBC does not say which one wins: this returns the secondary data's, {@see supplementalExpiryDate()} the other.
     */
    #[Override]
    public function expiryDate(): ?DateTimeImmutable
    {
        return $this->data()->expiryDate;
    }

    /**
     * The supplemental data identifier `14D`.
     */
    public function supplementalExpiryDate(): ?DateTimeImmutable
    {
        return $this->data()->supplementalExpiryDate;
    }

    public function expiryDateFormat(): ?HibcDateFormat
    {
        return $this->data()->expiryDateFormat;
    }

    /**
     * Supplemental data identifier `16D`.
     */
    #[Override]
    public function productionDate(): ?DateTimeImmutable
    {
        return $this->data()->manufactureDate;
    }

    /**
     * The quantity of the secondary data (`$$8`, `$$9`), else of the supplemental data identifier `Q`. When both are
     * present HIBC does not say which one wins: this returns the secondary data's, {@see supplementalQuantity()} the
     * other.
     *
     * @return int<0, 99999>|null
     */
    public function quantity(): ?int
    {
        return $this->data()->quantity;
    }

    /**
     * The supplemental data identifier `Q`.
     *
     * @return int<0, 99999>|null
     */
    public function supplementalQuantity(): ?int
    {
        return $this->data()->supplementalQuantity;
    }

    #[Override]
    protected function parse(string $value): string
    {
        HibcSyntax::parse($value);

        return $value;
    }

    /**
     * Read again from the canonical value: the parent's final constructor leaves no place for parsed state.
     */
    private function data(): HibcData
    {
        return HibcSyntax::parse($this->value);
    }
}
