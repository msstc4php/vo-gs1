<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaMod97;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IfaMod97::class)]
final class IfaMod97Test extends TestCase
{
    /**
     * IFA, Check Digit Calculations of PZN, PPN and Basic UDI-DI.
     *
     * @return iterable<string, array{non-empty-string, non-empty-string}>
     */
    public static function ifaExamples(): iterable
    {
        yield 'PPN 11 + PZN 03752864, sum 3409' => ['1103752864', '14'];
        yield 'Basic UDI-DI, sum 16203' => ['PP12345ABCD.12345678.90', '04'];
    }

    #[DataProvider('ifaExamples')]
    public function testComputesTheIfaExamples(string $data, string $expected): void
    {
        self::assertSame($expected, IfaMod97::compute($data));
    }

    public function testWeightsStartAtTwoOnTheLeft(): void
    {
        // "A" = 65 x 2 = 130, 130 mod 97 = 33; "AB" adds 66 x 3: 328 mod 97 = 37.
        self::assertSame('33', IfaMod97::compute('A'));
        self::assertSame('37', IfaMod97::compute('AB'));
        // 97 x 2 = 194, a multiple of 97: the leading zero is kept.
        self::assertSame('00', IfaMod97::compute('a'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidInput(): iterable
    {
        yield 'empty' => [''];
        yield 'non-ASCII' => ["\u{00C4}"];
    }

    #[DataProvider('invalidInput')]
    public function testRefusesEmptyOrNonAsciiInput(string $data): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('IFA check digit input must be ASCII.');

        IfaMod97::compute($data);
    }
}
