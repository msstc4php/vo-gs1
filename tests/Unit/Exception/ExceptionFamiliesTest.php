<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Exception;

use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidDigitalLinkException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidEpcUriException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversNothing]
final class ExceptionFamiliesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<InvalidValueObjectException>}>
     */
    public static function families(): iterable
    {
        yield 'identification key' => [InvalidIdentificationKeyException::class];
        yield 'element string' => [InvalidElementStringException::class];
        yield 'Digital Link' => [InvalidDigitalLinkException::class];
        yield 'EPC URI' => [InvalidEpcUriException::class];
        yield 'UDI' => [InvalidUdiException::class];
        yield 'marking code' => [InvalidMarkingCodeException::class];
    }

    /**
     * @param class-string<InvalidValueObjectException> $class
     */
    #[DataProvider('families')]
    public function testEveryFamilyIsAnInvalidValueObjectException(string $class): void
    {
        self::assertTrue(\is_a($class, InvalidValueObjectException::class, true));
        self::assertTrue(new ReflectionClass($class)->isFinal());
    }
}
