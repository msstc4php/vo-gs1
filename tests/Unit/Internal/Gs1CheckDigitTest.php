<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gs1CheckDigit::class)]
final class Gs1CheckDigitTest extends TestCase
{
    /**
     * GS1 General Specifications 26.0: Table 7-9 (the 18-digit example) and keys quoted in sections 2-4.
     *
     * @return iterable<string, array{non-empty-string}>
     */
    public static function officialExamples(): iterable
    {
        yield 'Table 7-9, 18 digits' => ['376104250021234569'];
        yield 'GTIN-14 (01)09521234500001' => ['09521234500001'];
        yield 'GTIN-14 (01)09506000134352' => ['09506000134352'];
        yield 'GTIN-14 (01)90614141000015' => ['90614141000015'];
        yield 'GTIN-14 (01)00012345678905' => ['00012345678905'];
        yield 'GTIN-14 (01)15012345678907' => ['15012345678907'];
        yield 'GLN (414)9521234500018' => ['9521234500018'];
    }

    /**
     * The GTIN-12 and GTIN-13 of the plan's review focus, on retail packs.
     *
     * @return iterable<string, array{non-empty-string}>
     */
    public static function retailExamples(): iterable
    {
        yield 'UPC-A 036000291452' => ['036000291452'];
        yield 'EAN-13 4006381333931' => ['4006381333931'];
    }

    #[DataProvider('officialExamples')]
    #[DataProvider('retailExamples')]
    public function testComputesTheCheckDigitOfOfficialExamples(string $key): void
    {
        self::assertSame(\substr($key, -1), Gs1CheckDigit::compute(\substr($key, 0, -1)));
        self::assertTrue(Gs1CheckDigit::isValid($key));
    }

    public function testCheckDigitZeroWhenTheSumIsAMultipleOfTen(): void
    {
        // 5*3 + 5*1 = 20.
        self::assertSame('0', Gs1CheckDigit::compute('55'));
        self::assertSame('0', Gs1CheckDigit::compute('0'));
    }

    public function testWeightsStartWithThreeOnTheRightmostDigit(): void
    {
        self::assertSame('7', Gs1CheckDigit::compute('1'));
        self::assertSame('9', Gs1CheckDigit::compute('10'));
    }

    public function testRefusesAWrongCheckDigit(): void
    {
        self::assertFalse(Gs1CheckDigit::isValid('376104250021234568'));
        self::assertFalse(Gs1CheckDigit::isValid('09521234500002'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notDigits(): iterable
    {
        yield 'empty' => [''];
        yield from self::nonDigitBodies();
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function nonDigitBodies(): iterable
    {
        yield 'letter' => ['12a4'];
        yield 'space' => ['12 4'];
        yield 'trailing newline' => ["1234\n"];
        yield 'fullwidth digit' => ['12３4'];
    }

    #[DataProvider('notDigits')]
    public function testComputeRefusesAnythingButDigits(string $input): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('GS1 check digit input must be ASCII digits.');

        Gs1CheckDigit::compute($input);
    }

    #[DataProvider('nonDigitBodies')]
    public function testIsValidRefusesAnythingButDigits(string $input): void
    {
        $this->expectException(LogicException::class);

        Gs1CheckDigit::isValid($input . '0');
    }

    public function testShortestValidInputs(): void
    {
        self::assertTrue(Gs1CheckDigit::isValid('00'));
        self::assertTrue(Gs1CheckDigit::isValid('17'));
        self::assertFalse(Gs1CheckDigit::isValid('18'));
        self::assertFalse(Gs1CheckDigit::isValid('1x'));
    }

    public function testIsValidIsFalseBelowTwoCharacters(): void
    {
        self::assertFalse(Gs1CheckDigit::isValid(''));
        self::assertFalse(Gs1CheckDigit::isValid('0'));
    }
}
