<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\ElementString;

use MSSTC4PHP\ValueObject\Gs1\ElementString\AidcMediaType;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AidcMediaType::class)]
#[UsesClass(ElementString::class)]
#[UsesClass(ElementStringSyntax::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(AiElement::class)]
#[UsesClass(AiLinters::class)]
final class AidcMediaTypeTest extends TestCase
{
    public function testTable312NamesVerbatim(): void
    {
        $labels = [];
        foreach (AidcMediaType::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        self::assertSame([
            '01' => 'Wristband',
            '02' => 'Order form',
            '03' => 'Sample Tube',
            '04' => 'Working list / Lab list / form',
            '05' => 'Test report',
            '06' => 'Delivery note / issue documentation',
            '07' => 'Intended recipient label (attached to container)',
            '08' => 'Label affixed to product',
            '09' => 'Identity card',
            '10' => 'Clinical or progress notes',
        ], $labels);
    }

    public function testValuesWithoutAnAssignmentHaveNoCase(): void
    {
        foreach (['00', '11', '29', '30', '59', '60', '79', '80', '99'] as $value) {
            self::assertNull(AidcMediaType::tryFrom($value), $value);
        }
    }

    public function testDecodesTheDataOfAi7241(): void
    {
        $element = ElementString::from('(7241)01');

        self::assertSame(AidcMediaType::Wristband, AidcMediaType::tryFrom((string) $element->get('7241')));
    }
}
