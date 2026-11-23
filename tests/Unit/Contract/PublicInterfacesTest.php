<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Contract;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink;
use MSSTC4PHP\ValueObject\Gs1\Key\IdentificationKey;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\BasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\Udi;
use MSSTC4PHP\ValueObject\Gs1\Udi\UdiFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversNothing]
final class PublicInterfacesTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string}>
     */
    public static function interfaces(): iterable
    {
        yield 'identification key' => [IdentificationKey::class];
        yield 'UDI' => [Udi::class];
        yield 'Basic UDI-DI' => [BasicUdiDi::class];
    }

    /**
     * @param class-string $interface
     */
    #[DataProvider('interfaces')]
    public function testEveryInterfaceIsAStringValueObjectForThisPackageOnly(string $interface): void
    {
        $reflection = new ReflectionClass($interface);

        self::assertTrue($reflection->isSubclassOf(StringConvertible::class));
        self::assertTrue($reflection->isSubclassOf(ValueObject::class));
        self::assertStringContainsString('Implemented by this package only; methods may be added in minor releases.', (string) $reflection->getDocComment());
    }

    public function testThePrimaryKeyOfADigitalLinkIsAValueObject(): void
    {
        $key = DigitalLink::from('https://example.com/01/09520123456788')->primaryKey();

        self::assertSame('09520123456788', (string) $key);
        self::assertSame('"09520123456788"', \json_encode($key));
    }

    public function testEveryUdiHasTheCommonProductionIdentifiers(): void
    {
        $udi = UdiFactory::fromString('+A123BJC5D6E71/$$52001510X3/16D20111212$');

        self::assertSame(['10X3', null], [$udi->lot(), $udi->serial()]);
        self::assertSame('2020-01-15', $udi->expiryDate()?->format('Y-m-d'));
        self::assertInstanceOf(DateTimeImmutable::class, $udi->productionDate());
        foreach (['deviceIdentifier', 'issuingAgency', 'lot', 'serial', 'expiryDate', 'productionDate'] as $method) {
            self::assertTrue(new ReflectionClass(Udi::class)->hasMethod($method), $method);
        }

        self::assertTrue(new ReflectionClass(BasicUdiDi::class)->hasMethod('issuingAgency'));
    }
}
