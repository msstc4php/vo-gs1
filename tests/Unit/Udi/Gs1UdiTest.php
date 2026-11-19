<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Udi;

use DateTimeImmutable;
use DateTimeZone;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiCharset;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Udi\Gs1Udi;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gs1Udi::class)]
#[UsesClass(ElementString::class)]
#[UsesClass(ElementStringSyntax::class)]
#[UsesClass(AiElement::class)]
#[UsesClass(AiLinters::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(AiCharset::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(Gs1Charsets::class)]
#[UsesClass(Gs1Dates::class)]
#[UsesClass(KeyRules::class)]
#[UsesClass(Gtin::class)]
final class Gs1UdiTest extends TestCase
{
    public function testAGs1ElementStringWithAGtin(): void
    {
        $udi = Gs1Udi::from('(01)09506000134352(17)201225(10)ABC123(11)200101(21)XYZ');
        $today = new DateTimeImmutable('2026-11-19', new DateTimeZone('UTC'));

        self::assertSame('(01)09506000134352(17)201225(10)ABC123(11)200101(21)XYZ', (string) $udi);
        self::assertSame('09506000134352', $udi->deviceIdentifier());
        self::assertSame(IssuingAgency::Gs1, $udi->issuingAgency());
        self::assertSame('09506000134352', (string) $udi->gtin());
        self::assertSame('ABC123', $udi->lot());
        self::assertSame('XYZ', $udi->serial());
        self::assertSame('2020-12-25', $udi->expiryDate($today)?->format('Y-m-d'));
        self::assertSame('2020-01-01', $udi->productionDate($today)?->format('Y-m-d'));
        self::assertSame((string) $udi, (string) $udi->elementString());
    }

    public function testScannerDataIsTheSameUdi(): void
    {
        $scanned = Gs1Udi::from("]d201095060001343521721122510ABC\x1D21XYZ");

        self::assertTrue($scanned->equals(Gs1Udi::from('(01)09506000134352(17)211225(10)ABC(21)XYZ')));
        self::assertNull(Gs1Udi::from('(01)09506000134352')->lot());
    }

    public function testAGs1UdiWithoutAi01IsRefused(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('GS1 UDI must carry AI (01), the GTIN of the device.');

        Gs1Udi::from('(10)ABC123(17)201225');
    }

    public function testAnInvalidElementStringIsRefusedWithItsCause(): void
    {
        try {
            Gs1Udi::from('(01)09506000134353');
            self::fail('A wrong GTIN check digit must be refused.');
        } catch (InvalidUdiException $exception) {
            self::assertStringStartsWith('GS1 UDI is not a valid element string: Element string AI (01)', $exception->getMessage());
            self::assertInstanceOf(InvalidElementStringException::class, $exception->getPrevious());
        }
    }
}
