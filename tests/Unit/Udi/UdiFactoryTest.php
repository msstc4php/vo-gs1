<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Udi;

use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
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
use MSSTC4PHP\ValueObject\Gs1\Internal\HibcData;
use MSSTC4PHP\ValueObject\Gs1\Internal\HibcSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\IsbtData;
use MSSTC4PHP\ValueObject\Gs1\Internal\IsbtSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Internal\Mod43;
use MSSTC4PHP\ValueObject\Gs1\Internal\UdiDates;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Udi\Gs1Udi;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\IsbtUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\Udi;
use MSSTC4PHP\ValueObject\Gs1\Udi\UdiFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UdiFactory::class)]
#[UsesClass(Gs1Udi::class)]
#[UsesClass(HibcUdi::class)]
#[UsesClass(HibcSyntax::class)]
#[UsesClass(HibcData::class)]
#[UsesClass(Mod43::class)]
#[UsesClass(IsbtUdi::class)]
#[UsesClass(IsbtSyntax::class)]
#[UsesClass(IsbtData::class)]
#[UsesClass(UdiDates::class)]
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
final class UdiFactoryTest extends TestCase
{
    /**
     * @return iterable<string, array{string, class-string<Udi>, string}>
     */
    public static function leadingForms(): iterable
    {
        yield '(01)' => ['(01)09506000134352(10)ABC', Gs1Udi::class, '09506000134352'];
        yield '(17) first' => ['(17)201225(01)09506000134352', Gs1Udi::class, '09506000134352'];
        yield '01' => ['0109506000134352', Gs1Udi::class, '09506000134352'];
        yield ']d2' => [']d20109506000134352', Gs1Udi::class, '09506000134352'];
        yield 'GS' => ["\x1D0109506000134352", Gs1Udi::class, '09506000134352'];
        yield '^' => ['^0109506000134352', Gs1Udi::class, '09506000134352'];
        yield '+' => ['+A123BJC5D6E71G', HibcUdi::class, '+A123BJC5D6E71G'];
        yield ']C0+' => [']C0+A123BJC5D6E71G', HibcUdi::class, '+A123BJC5D6E71G'];
        yield ']C0=/' => [']C0=/A9997XYZ100T0479=A99991712345600=,000012', IsbtUdi::class, 'A9997XYZ100T0479'];
        yield ']d1=+' => [']d1=+03000=/A9997XYZ100T0479=A99991712345600=,000012', IsbtUdi::class, 'A9997XYZ100T0479'];
        yield '=+' => ['=+04000=/A9997XYZ100T0479=A99991712345600=,000012=>019031', IsbtUdi::class, 'A9997XYZ100T0479'];
        yield '=/' => ['=/A9997XYZ100T0479=A99991712345600=,000012', IsbtUdi::class, 'A9997XYZ100T0479'];
    }

    /**
     * @param class-string<Udi> $class
     */
    #[DataProvider('leadingForms')]
    public function testTheLeadingCharacterNamesTheAgency(string $value, string $class, string $deviceIdentifier): void
    {
        $udi = UdiFactory::fromString($value);

        self::assertInstanceOf($class, $udi);
        self::assertSame($deviceIdentifier, $udi->deviceIdentifier());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownForms(): iterable
    {
        yield 'empty' => [''];
        yield 'letter' => ['A9997XYZ100T0479'];
        yield 'space' => [' (01)09506000134352'];
        yield 'human-readable asterisks' => ['*+A123BJC5D6E71G*'];
    }

    #[DataProvider('unknownForms')]
    public function testAnInputOfNoAgencyIsRefused(string $value): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('UDI does not start like a GS1, HIBC or ISBT 128 UDI.');

        UdiFactory::fromString($value);
    }

    public function testAnAmpersandIsIsbt128(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('ISBT 128 UDI must begin with the Processor Product Identification Code "=/".');

        UdiFactory::fromString('&,1LOT');
    }

    public function testDataBehindASymbologyIdentifierIsGs1OtherwiseAndValidatedThere(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('GS1 UDI is not a valid element string');

        UdiFactory::fromString(']C0A9997');
    }

    public function testABracketedFormBehindASymbologyIdentifierIsRefusedByGs1(): void
    {
        // A scanner never transmits brackets: GS1 refuses the mix instead of another agency taking it.
        try {
            UdiFactory::fromString(']C1(01)09506000134352(17)201225(10)ABC');
            self::fail('Expected InvalidUdiException.');
        } catch (InvalidUdiException $exception) {
            self::assertSame('GS1 UDI is not a valid element string: Element string must start with an AI.', $exception->getMessage());
        }

        self::assertSame('(01)09506000134352', (string) UdiFactory::fromString(']C10109506000134352'));
    }

    public function testTheAgencyClassValidatesTheRest(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('HIBC UDI has a wrong Mod 43 check character.');

        UdiFactory::fromString('+A123BJC5D6E71H');
    }
}
