<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi;

use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;

/**
 * The Basic UDI-DI of the EU MDR and IVDR, the EUDAMED key of a device model: at most 25 characters with check
 * characters "based on an algorithm defined by the issuing entity" (European Commission, quoted in ICCBBA ST-017,
 * Appendix 1). It does not appear on the device.
 *
 * Implemented by this package only; methods may be added in minor releases.
 */
interface BasicUdiDi extends StringConvertible, ValueObject
{
    public function issuingAgency(): IssuingAgency;
}
