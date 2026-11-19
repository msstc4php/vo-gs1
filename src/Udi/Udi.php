<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;

/**
 * A Unique Device Identifier as one issuing agency encodes it: the device identifier (UDI-DI) and the production
 * identifiers (UDI-PI).
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
}
