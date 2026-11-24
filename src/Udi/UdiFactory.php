<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;

/**
 * Builds the UDI of the issuing agency the data names. A leading symbology identifier (`]` and two characters) is set
 * aside first; then `+` is HIBC (the HIBC Supplier Labeling flag), `=` or `&` is ISBT 128 (an ISBT 128 data
 * identifier), and a GS1 element string starts with `(` (bracketed AI), a digit (an AI), or an FNC1 (GS or `^`), or
 * is any data behind a symbology identifier. Anything else is refused, never guessed.
 */
final class UdiFactory
{
    private function __construct()
    {
    }

    public static function fromString(string $value): Udi
    {
        $data = \str_starts_with($value, ']') ? \substr($value, 3) : $value;

        return match (true) {
            \str_starts_with($data, '+') => new HibcUdi($data),
            \str_starts_with($data, '=') || \str_starts_with($data, '&') => new IsbtUdi($data),
            $data !== $value || ($value !== '' && \str_contains("(^\x1D0123456789", $value[0])) => new Gs1Udi($value),
            default => throw new InvalidUdiException('UDI does not start like a GS1, HIBC or ISBT 128 UDI.'),
        };
    }

    /** Null when no agency fits or the agency class refuses the data; fromString() says which. */
    public static function tryFromString(string $value): ?Udi
    {
        try {
            return self::fromString($value);
        } catch (InvalidValueObjectException) {
            return null;
        }
    }
}
