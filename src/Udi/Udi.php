<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;

/**
 * A Unique Device Identifier as one issuing agency encodes it: the device identifier (UDI-DI) and the production
 * identifiers (UDI-PI).
 *
 * Implemented by this package only; methods may be added in minor releases.
 */
interface Udi extends StringConvertible, ValueObject
{
    /**
     * The UDI-DI in the issuing agency's own form.
     *
     * @return non-empty-string
     */
    public function deviceIdentifier(): string;

    public function issuingAgency(): IssuingAgency;

    /**
     * @return non-empty-string|null
     */
    public function lot(): ?string;

    /**
     * @return non-empty-string|null
     */
    public function serial(): ?string;

    public function expiryDate(): ?DateTimeImmutable;

    public function productionDate(): ?DateTimeImmutable;
}
