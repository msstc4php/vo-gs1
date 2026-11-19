<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

/**
 * The organisations that issue UDIs: GS1, HIBCC and ICCBBA are the FDA-accredited issuing agencies; IFA is an EU
 * issuing entity and issues Basic UDI-DIs only in this package.
 *
 * @see docs/sources/GS1.md sections 5 to 7
 */
enum IssuingAgency: string
{
    case Gs1 = 'GS1';
    case Hibcc = 'HIBCC';
    case Iccbba = 'ICCBBA';
    case Ifa = 'IFA';
}
