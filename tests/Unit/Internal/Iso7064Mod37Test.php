<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Iso7064Mod37;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Iso7064Mod37::class)]
final class Iso7064Mod37Test extends TestCase
{
    public function testComputesTheExampleOfSt017Appendix1(): void
    {
        self::assertSame('08', Iso7064Mod37::compute('A9990ABC123T0480'));
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function singleCharacters(): iterable
    {
        // One character c at position 1: (38 - 2c) mod 37.
        yield '0 gives 38 mod 37' => ['0', '01'];
        yield '9' => ['9', '20'];
        yield 'A is 10' => ['A', '18'];
        yield 'Z is 35, sum 70' => ['Z', '05'];
        yield 'S is 28, sum 56: 38 - 19' => ['S', '19'];
    }

    #[DataProvider('singleCharacters')]
    public function testCharacterValuesAndTheTwoDigitResult(string $data, string $expected): void
    {
        self::assertSame($expected, Iso7064Mod37::compute($data));
    }

    public function testWeightsGrowByPowersOfTwoFromTheRight(): void
    {
        // "10": 1 x 4 + 0 x 2 = 4, (38 - 4) mod 37 = 34; "01": 0 x 4 + 1 x 2 = 2, 36.
        self::assertSame('34', Iso7064Mod37::compute('10'));
        self::assertSame('36', Iso7064Mod37::compute('01'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidInput(): iterable
    {
        yield 'empty' => [''];
        yield 'lower case' => ['a'];
        yield 'hyphen' => ['A-1'];
    }

    #[DataProvider('invalidInput')]
    public function testRefusesCharactersOutsideTheAlphabet(string $data): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('MOD 37-2 input must be digits and capital letters.');

        Iso7064Mod37::compute($data);
    }
}
