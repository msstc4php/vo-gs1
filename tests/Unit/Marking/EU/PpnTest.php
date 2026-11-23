<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Marking\EU;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaMod97;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\Ppn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ppn::class)]
#[UsesClass(IfaMod97::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(Gs1Charsets::class)]
#[UsesClass(KeyRules::class)]
#[UsesClass(Gtin::class)]
final class PpnTest extends TestCase
{
    public function testIfaCheckDigitExample(): void
    {
        // IFA, Check Digit Calculations of PZN, PPN and Basic UDI-DI: sum 3409, remainder 14.
        $ppn = Ppn::from('110375286414');

        self::assertSame('11', $ppn->praCode());
        self::assertSame('03752864', $ppn->productNumber());
        self::assertSame('03752864', $ppn->pzn());
        self::assertTrue($ppn->equals(Ppn::fromParts('11', '03752864')));
    }

    public function testThePznBecomesTheNtinOfIfaSection43(): void
    {
        // PPN-Code Specification 3.06, section 5.2.1: PPN 110375286414 and NTIN 04150037528643 code the same PZN.
        self::assertSame('04150037528643', (string) Ppn::from('110375286414')->toNtin());
        self::assertSame('04150123456782', (string) Ppn::from('111234567842')->toNtin());
    }

    public function testOtherAgenciesAreFormatOnly(): void
    {
        $ppn = Ppn::fromParts('14', 'ABC1234');

        self::assertSame('14ABC1234' . IfaMod97::compute('14ABC1234'), (string) $ppn);
        self::assertSame('14', $ppn->praCode());
        self::assertSame('ABC1234', $ppn->productNumber());
        self::assertNull($ppn->pzn());
        self::assertNull($ppn->toNtin());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalid(): iterable
    {
        yield 'wrong check digits' => ['110375286415', 'PPN has wrong check digits.'];
        yield 'too short' => ['1103752864', 'PPN must be a 2-character PRA Code, a 7 to 24 character product number of 0-9 and A-Z and two check digits.'];
        yield 'too long' => [\str_repeat('1', 29), 'PPN must be a 2-character PRA Code, a 7 to 24 character product number of 0-9 and A-Z and two check digits.'];
        yield 'lower case' => ['11abcdefgh00', 'PPN must be a 2-character PRA Code, a 7 to 24 character product number of 0-9 and A-Z and two check digits.'];
        yield 'German PPN that is not a PZN' => ['11ABCDEFGH' . IfaMod97::compute('11ABCDEFGH'), 'PPN with PRA Code 11 must carry an 8-digit PZN with a valid check digit.'];
        yield 'PZN with a wrong check digit' => ['1103752865' . IfaMod97::compute('1103752865'), 'PPN with PRA Code 11 must carry an 8-digit PZN with a valid check digit.'];
        yield 'PZN whose remainder is 10' => ['1100000030' . IfaMod97::compute('1100000030'), 'PPN with PRA Code 11 must carry an 8-digit PZN with a valid check digit.'];
    }

    #[DataProvider('invalid')]
    public function testInvalidPpnsAreRefused(string $value, string $message): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage($message);

        Ppn::from($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidParts(): iterable
    {
        yield 'lower case before valid parts' => ['x1', '103752864'];
        yield 'hyphen after valid parts' => ['11', '03752864-'];
        yield 'trailing newline' => ['11', "03752864\n"];
        yield 'too short' => ['11', '0375'];
    }

    #[DataProvider('invalidParts')]
    public function testFromPartsRefusesMalformedParts(string $praCode, string $productNumber): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage('PPN parts must be 9 to 26 characters of 0-9 and A-Z.');

        Ppn::fromParts($praCode, $productNumber);
    }

    public function testFromPartsRefusesABadProductNumber(): void
    {
        $this->expectException(InvalidMarkingCodeException::class);

        Ppn::fromParts('11', '0375286');
    }
}
