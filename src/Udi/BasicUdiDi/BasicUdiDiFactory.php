<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;

/**
 * Builds the Basic UDI-DI of the issuing entity whose format and check characters the value satisfies. A value valid
 * for several entities throws vo-base `AmbiguousValueException` with the candidates; one valid for none throws
 * `InvalidUdiException`.
 */
final class BasicUdiDiFactory
{
    /** @var list<class-string<Gs1BasicUdiDi|HibccBasicUdiDi|IccbbaBasicUdiDi|IfaBasicUdiDi>> */
    private const array TYPES = [Gs1BasicUdiDi::class, HibccBasicUdiDi::class, IccbbaBasicUdiDi::class, IfaBasicUdiDi::class];

    private function __construct()
    {
    }

    public static function fromString(string $value): BasicUdiDi
    {
        $matches = \array_values(\array_filter(self::TYPES, static fn (string $type): bool => $type::isValid($value)));

        return match (\count($matches)) {
            0 => throw new InvalidUdiException('Basic UDI-DI fits no issuing entity (GS1, HIBCC, ICCBBA, IFA).'),
            1 => new $matches[0]($value),
            default => throw new AmbiguousValueException('Basic UDI-DI', $matches),
        };
    }
}
