<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Contract;

use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\BasicUdiDiFactory;
use MSSTC4PHP\ValueObject\Gs1\Udi\UdiFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversNothing]
final class StaticFactoriesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function factories(): iterable
    {
        yield 'UDI' => [UdiFactory::class];
        yield 'Basic UDI-DI' => [BasicUdiDiFactory::class];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('factories')]
    public function testAStaticFactoryCannotBeInstantiated(string $class): void
    {
        self::assertFalse(new ReflectionClass($class)->isInstantiable());
    }
}
