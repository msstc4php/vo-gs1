<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\ElementString;

use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementStringBuilder;
use MSSTC4PHP\ValueObject\Gs1\ElementString\UnmetRequirement;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ElementStringBuilder::class)]
#[UsesClass(ElementString::class)]
#[UsesClass(UnmetRequirement::class)]
#[UsesClass(ElementStringSyntax::class)]
#[UsesClass(AiElement::class)]
#[UsesClass(Gs1Dates::class)]
#[UsesClass(AiLinters::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(Gs1CheckDigit::class)]
final class ElementStringBuilderTest extends TestCase
{
    public function testBuildsInTheOrderGivenAndEscapesParentheses(): void
    {
        $element = new ElementStringBuilder()
            ->with('01', '09506000134352')
            ->with('17', '261231')
            ->with('21', 'A(B')
            ->build()
        ;

        self::assertSame('(01)09506000134352(17)261231(21)A\(B', (string) $element);
        self::assertSame('A(B', $element->serial());
    }

    public function testIsImmutable(): void
    {
        $empty = new ElementStringBuilder();
        $withGtin = $empty->with('01', '09506000134352');

        self::assertNotSame($empty, $withGtin);
        self::assertSame('(01)09506000134352', (string) $withGtin->build());
        self::assertSame('(01)09506000134352(10)X', (string) $withGtin->with('10', 'X')->build());
        self::assertSame('(01)09506000134352', (string) $withGtin->build());

        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string is empty.');
        $empty->build();
    }

    public function testUnknownAiIsRefusedWithoutEchoingIt(): void
    {
        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string contains an unknown AI.');

        new ElementStringBuilder()->with('01)0950600013435(10', 'X');
    }

    public function testMissingReqPartnerStillBuilds(): void
    {
        $element = new ElementStringBuilder()->with('10', 'ABC')->build();

        self::assertSame('(10)ABC', (string) $element);
        self::assertSame('10', $element->unmetRequirements()[0]->ai);
    }

    public function testValuesAreValidatedOnBuild(): void
    {
        $builder = new ElementStringBuilder()->with('01', '09506000134352')->with('10', 'A#B');

        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string AI (10) data contains a character outside its GS1 character set.');
        $builder->build();
    }

    public function testValueCannotSmuggleAnotherAi(): void
    {
        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string AI (21) data contains a character outside its GS1 character set.');

        new ElementStringBuilder()->with('01', '09506000134352')->with('21', 'X\\')->with('10', 'Y');
    }
}
