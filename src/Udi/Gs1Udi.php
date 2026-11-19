<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A GS1 UDI: a GS1 element string carrying the GTIN in AI (01) as the UDI-DI, with production identifiers such as
 * (10) batch, (11) production date, (17) expiry date and (21) serial. Every notation {@see ElementString} accepts is
 * accepted; the value is the bracketed form.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 2.6.13
 * @see docs/sources/GS1.md "Element strings"
 */
final class Gs1Udi extends AbstractStringValueObject implements Udi
{
    /**
     * The 14-digit GTIN of AI (01).
     */
    #[Override]
    public function deviceIdentifier(): string
    {
        return $this->gtin()->toGtin14();
    }

    #[Override]
    public function issuingAgency(): IssuingAgency
    {
        return IssuingAgency::Gs1;
    }

    public function gtin(): Gtin
    {
        return $this->elementString()->gtin() ?? throw new InvalidUdiException('GS1 UDI lost its AI (01).');
    }

    public function elementString(): ElementString
    {
        return new ElementString($this->value);
    }

    /**
     * AI (10).
     */
    public function lot(): ?string
    {
        return $this->elementString()->batch();
    }

    /**
     * AI (21).
     */
    public function serial(): ?string
    {
        return $this->elementString()->serial();
    }

    /**
     * AI (17); see {@see ElementString::expiryDate()} for the century and `$today`.
     *
     * @throws InvalidElementStringException when the century chosen for `$today` has no such day (29 February)
     */
    public function expiryDate(?DateTimeImmutable $today = null): ?DateTimeImmutable
    {
        return $this->elementString()->expiryDate($today);
    }

    /**
     * AI (11), read as {@see expiryDate()}.
     *
     * @throws InvalidElementStringException when the century chosen for `$today` has no such day (29 February)
     */
    public function productionDate(?DateTimeImmutable $today = null): ?DateTimeImmutable
    {
        return $this->elementString()->productionDate($today);
    }

    #[Override]
    protected function parse(string $value): string
    {
        try {
            $elementString = new ElementString($value);
        } catch (InvalidElementStringException $exception) {
            throw new InvalidUdiException('GS1 UDI is not a valid element string: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
        }

        if (!$elementString->has('01')) {
            throw new InvalidUdiException('GS1 UDI must carry AI (01), the GTIN of the device.');
        }

        return $elementString->value;
    }
}
