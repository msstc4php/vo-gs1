<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Exception;

use DomainException;

/**
 * Thrown by a date accessor of a valid value when the century GS1's sliding window picks for the given day has no such
 * calendar day (`000229` read as 2100-02-29). The value itself stays valid, so this is not an
 * {@see \MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException}.
 */
final class UnresolvableDateException extends DomainException
{
}
