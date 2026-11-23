<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Marking\EU;

use DateTimeImmutable;
use DateTimeZone;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
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
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaMod97;
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaPackData;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\FmdPackCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\Ppn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FmdPackCode::class)]
#[CoversClass(IfaPackData::class)]
#[UsesClass(Ppn::class)]
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
#[UsesClass(IfaMod97::class)]
#[UsesClass(KeyRules::class)]
#[UsesClass(Gtin::class)]
final class FmdPackCodeTest extends TestCase
{
    private const string RS = "\x1E";

    private const string GS = "\x1D";

    private const string EOT = "\x04";

    /** PPN-Code Specification 3.06, Appendix C.5, Example 3 (scanner output, ASC format). */
    private const string IFA = '[)>' . self::RS . '06' . self::GS . '9N111234567842' . self::GS . '1T1A234B5' . self::GS . 'D151231' . self::GS . 'S1234567890123456' . self::RS . self::EOT;

    /** The same pack in the GS1 format, Appendix C.5, Example 2 (NTIN of the same PZN). */
    private const string GS1 = '0104150123456782101A234B5' . self::GS . '17151231211234567890123456';

    public function testTheIfaFormatOfAppendixC(): void
    {
        $code = FmdPackCode::from(self::IFA);

        self::assertSame(self::IFA, (string) $code);
        self::assertTrue($code->isIfaFormat());
        self::assertInstanceOf(Ppn::class, $code->productCode());
        self::assertSame('111234567842', (string) $code->productCode());
        self::assertSame('1234567890123456', $code->serial());
        self::assertSame('1A234B5', $code->batch());
        self::assertSame('151231', $code->expiry());
        self::assertSame('2015-12-31T00:00:00+00:00', $code->expiryDate()->format(DATE_ATOM));
        self::assertSame('12345678', $code->pzn());
        self::assertNull($code->elementString());
    }

    public function testTheGs1FormatOfAppendixC(): void
    {
        $code = FmdPackCode::from(self::GS1);
        $today = new DateTimeImmutable('2026-11-19', new DateTimeZone('UTC'));

        self::assertSame('0104150123456782101A234B5' . self::GS . '17151231211234567890123456', (string) $code);
        self::assertFalse($code->isIfaFormat());
        self::assertInstanceOf(Gtin::class, $code->productCode());
        self::assertSame('1234567890123456', $code->serial());
        self::assertSame('1A234B5', $code->batch());
        self::assertSame('151231', $code->expiry());
        self::assertSame('2015-12-31', $code->expiryDate($today)->format('Y-m-d'));
        self::assertSame('12345678', $code->pzn());
        self::assertInstanceOf(ElementString::class, $code->elementString());
    }

    public function testBothFormatsOfOnePackGiveTheSameProductCode(): void
    {
        $ifa = FmdPackCode::from(self::IFA)->productCode();
        $gs1 = FmdPackCode::from(self::GS1)->productCode();

        self::assertInstanceOf(Ppn::class, $ifa);
        self::assertInstanceOf(Gtin::class, $gs1);
        self::assertTrue($gs1->equals($ifa->toNtin() ?? self::fail('PRA 11 has an NTIN.')));
        self::assertSame(FmdPackCode::from(self::IFA)->pzn(), FmdPackCode::from(self::GS1)->pzn());
    }

    public function testFormsWithoutTrailerOrInOtherNotationsAreTheSameValue(): void
    {
        $withoutTrailer = \substr(self::IFA, 0, -2);

        self::assertSame(self::IFA, (string) FmdPackCode::from($withoutTrailer));
        self::assertSame(self::IFA, (string) FmdPackCode::from($withoutTrailer . self::RS));
        self::assertTrue(FmdPackCode::from('(01)04150123456782(10)1A234B5(17)151231(21)1234567890123456')->equals(FmdPackCode::from(self::GS1)));
        self::assertTrue(FmdPackCode::from(']d2' . self::GS1)->equals(FmdPackCode::from(self::GS1)));
    }

    public function testAScannedIfaMessageAfterASymbologyIdentifierIsTheIfaFormat(): void
    {
        foreach ([']d1', ']d2'] as $prefix) {
            $code = FmdPackCode::from($prefix . self::IFA);

            self::assertTrue($code->isIfaFormat(), $prefix);
            self::assertSame(self::IFA, (string) $code, $prefix);
        }

        self::assertTrue(FmdPackCode::from(']d2' . self::GS1)->equals(FmdPackCode::from(self::GS1)));
    }

    public function testMultiMarketPacksKeepTheirNationalNumbers(): void
    {
        // PPN-Code Specification 3.06, section 5.3.2, Example 1, and 5.3.3 (ASC format with 8P).
        $gs1 = FmdPackCode::from('(01)08701234567896(21)1234567890ABCD(10)1234AB(17)210600(710)12345678(711)91234567');
        $ifa = FmdPackCode::from('[)>' . self::RS . '06' . self::GS . '9N111234567842' . self::GS . 'S1234567890ABCD' . self::GS . '1T1234AB' . self::GS . 'D210600' . self::GS . '8P08701234567896' . self::GS . '8P03400912345676' . self::RS . self::EOT);

        self::assertSame('12345678', $gs1->elementString()?->get('710'));
        self::assertNull($gs1->pzn());
        self::assertSame('2021-06-30', $gs1->expiryDate(new DateTimeImmutable('2026-11-19'))->format('Y-m-d'));
        self::assertSame('2021-06-30', $ifa->expiryDate()->format('Y-m-d'));
        self::assertSame('12345678', $ifa->pzn());
        self::assertSame((string) $ifa, (string) FmdPackCode::from((string) $ifa));
    }

    public function testAPpnOfAnotherAgencyHasNoPzn(): void
    {
        $ppn = Ppn::fromParts('14', '1234567');
        $code = FmdPackCode::from('[)>' . self::RS . '06' . self::GS . '9N' . $ppn . self::GS . 'S1' . self::GS . '1T1' . self::GS . 'D300100');

        self::assertNull($code->pzn());
        self::assertSame('2030-01-31', $code->expiryDate()->format('Y-m-d'));
    }

    public function testTwentyCharacterSerialAndBatchAreAccepted(): void
    {
        $twenty = \str_repeat('A', 20);
        $code = FmdPackCode::from('[)>' . self::RS . '06' . self::GS . '9N111234567842' . self::GS . '1T' . $twenty . self::GS . 'D210600' . self::GS . 'S' . $twenty);

        self::assertSame($twenty, $code->serial());
        self::assertSame($twenty, $code->batch());
    }

    public function testFurtherDataIdentifiersAreKeptAsGiven(): void
    {
        $code = FmdPackCode::from('[)>' . self::RS . '06' . self::GS . '9N111234567842' . self::GS . '33Lhttp://Example.com' . self::GS . 'S1' . self::GS . '1T1' . self::GS . 'D210600' . self::GS . '16D20210101');

        self::assertStringContainsString(self::GS . '33Lhttp://Example.com' . self::GS, (string) $code);
    }

    /**
     * @return iterable<string, array{string, non-empty-string}>
     */
    public static function invalid(): iterable
    {
        $head = '[)>' . self::RS . '06' . self::GS;

        yield 'empty' => ['', 'FMD pack code is empty.'];
        yield 'too long' => [\str_repeat('1', 513), 'FMD pack code must have at most 512 characters.'];
        yield 'GS1 without batch' => ['(01)04150123456782(17)151231(21)1234567890123456', 'FMD pack code (GS1) must carry AI (01), (21), (10) and (17).'];
        yield 'GS1 invalid element string' => ['(01)04150123456783(10)1(17)151231(21)1', 'FMD pack code (GS1) is not a valid element string: Element string AI data fails the GS1 csum check.'];
        yield 'IFA without expiry' => [$head . '9N111234567842' . self::GS . '1T1A234B5' . self::GS . 'S1', 'FMD pack code (IFA) must carry DI 9N, S, 1T and D once each.'];
        yield 'IFA with two serials' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1' . self::GS . 'S2', 'FMD pack code (IFA) must carry DI 9N, S, 1T and D once each.'];
        yield 'IFA serial of 21' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S' . \str_repeat('1', 21), 'FMD pack code (IFA) DI S must have 1 to 20 characters of GS1 character set 82.'];
        yield 'IFA batch with a hash' => [$head . '9N111234567842' . self::GS . '1T1#' . self::GS . 'D151231' . self::GS . 'S1', 'FMD pack code (IFA) DI 1T must have 1 to 20 characters of GS1 character set 82.'];
        yield 'IFA bad expiry' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151331' . self::GS . 'S1', 'FMD pack code (IFA) DI D must be a date YYMMDD (DD 00 allowed).'];
        yield 'IFA 29 February of a non-leap year' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D230229' . self::GS . 'S1', 'FMD pack code (IFA) DI D must be a date YYMMDD (DD 00 allowed).'];
        yield 'IFA bad PPN' => [$head . '9N111234567843' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1', 'FMD pack code (IFA) DI 9N is not a valid PPN: PPN has wrong check digits.'];
        yield 'at the length bound' => [\str_repeat('1', 512), 'FMD pack code (GS1) must carry AI (01), (21), (10) and (17).'];
        yield 'IFA 8P of 13 digits' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1' . self::GS . '8P4150123456782', 'FMD pack code (IFA) DI 8P must be a GTIN of 14 digits.'];
        yield 'IFA bad 8P' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1' . self::GS . '8P123', 'FMD pack code (IFA) DI 8P must be a GTIN of 14 digits.'];
        yield 'IFA field without DI' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1' . self::GS . '123', 'FMD pack code (IFA) has a field without a data identifier.'];
        yield 'IFA empty field' => [$head . '9N111234567842' . self::GS . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1', 'FMD pack code (IFA) has a field without a data identifier.'];
        yield 'IFA control character in other data' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1' . self::GS . "33L\x05", 'FMD pack code (IFA) has a field without a data identifier.'];
        yield 'IFA data after the trailer' => [$head . '9N111234567842' . self::GS . '1T1' . self::GS . 'D151231' . self::GS . 'S1' . self::RS . self::EOT . 'X', 'FMD pack code (IFA) has a field without a data identifier.'];
    }

    /**
     * @param non-empty-string $message
     */
    #[DataProvider('invalid')]
    public function testInvalidCodesAreRefused(string $value, string $message): void
    {
        try {
            FmdPackCode::from($value);
            self::fail('Expected InvalidMarkingCodeException.');
        } catch (InvalidMarkingCodeException $exception) {
            self::assertStringStartsWith($message, $exception->getMessage());
        }
    }
}
