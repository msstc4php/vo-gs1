<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;

/**
 * Builds the UDI of the issuing agency its leading character names: `+` is HIBC (the HIBC Supplier Labeling flag),
 * `=` is ISBT 128 (an ISBT 128 data identifier); a GS1 element string starts with `(` (bracketed AI), a digit (an
 * AI), `]` (symbology identifier) or an FNC1 (GS or `^`). These sets do not overlap; anything else is refused, never
 * guessed.
 */
final class UdiFactory
{
    public static function fromString(string $value): Udi
    {
        return match (true) {
            \str_starts_with($value, '+') => new HibcUdi($value),
            \str_starts_with($value, '=') => new IsbtUdi($value),
            $value !== '' && \str_contains("(]^\x1D0123456789", $value[0]) => new Gs1Udi($value),
            default => throw new InvalidUdiException('UDI does not start like a GS1, HIBC or ISBT 128 UDI.'),
        };
    }
}
