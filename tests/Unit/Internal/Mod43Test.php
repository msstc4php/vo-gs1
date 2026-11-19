<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Mod43;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mod43::class)]
final class Mod43Test extends TestCase
{
    public function testComputesTheHibccAppendixBExample(): void
    {
        self::assertSame('G', Mod43::compute('+A123BJC5D6E71'));
    }

    /**
     * Table B1 of ANSI/HIBC 2.6 Appendix B: one character per value, so a single character is its own check.
     *
     * @return iterable<string, array{non-empty-string}>
     */
    public static function tableB1(): iterable
    {
        foreach (\str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%') as $character) {
            yield \sprintf('"%s"', $character) => [$character];
        }
    }

    #[DataProvider('tableB1')]
    public function testEveryCharacterOfTableB1HasItsOwnValue(string $character): void
    {
        self::assertSame($character, Mod43::compute($character));
    }

    public function testSumWrapsAroundModulo43(): void
    {
        // % = 42, 1 = 1: 43 mod 43 = 0.
        self::assertSame('0', Mod43::compute('%1'));
        self::assertSame('1', Mod43::compute('%2'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsideTheTable(): iterable
    {
        yield 'empty' => [''];
        yield 'lower case' => ['+a123'];
        yield 'asterisk' => ['*A123*'];
        yield 'newline' => ["+A123\n"];
    }

    #[DataProvider('outsideTheTable')]
    public function testRefusesCharactersOutsideTableB1(string $input): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Mod 43 input must be characters of HIBCC Table B1.');

        Mod43::compute($input);
    }
}
