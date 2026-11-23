<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;

/**
 * A GS1 identification key (GTIN, GLN, SSCC, …), the primary key of a GS1 Digital Link.
 *
 * Implemented by this package only; methods may be added in minor releases.
 */
interface IdentificationKey extends StringConvertible, ValueObject
{
    /**
     * The GS1 Prefix range the key's leading digits fall in.
     */
    public function gs1Prefix(): Gs1Prefix;
}
