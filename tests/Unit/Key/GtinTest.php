<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Key;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gln;
use MSSTC4PHP\ValueObject\Gs1\Key\Gs1Prefix;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Key\GtinFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gtin::class)]
#[CoversClass(GtinFormat::class)]
#[CoversClass(KeyRules::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(Gln::class)]
#[UsesClass(GmnCheckCharacters::class)]
#[UsesClass(Gs1Prefix::class)]
final class GtinTest extends TestCase
{
    public function testEan13AndGtin14AreOneValueThatKeepsTheFormGiven(): void
    {
        $ean = new Gtin('4006381333931');
        $gtin14 = new Gtin('04006381333931');

        self::assertTrue($ean->equals($gtin14));
        self::assertTrue($gtin14->equals($ean));
        self::assertSame('4006381333931', (string) $ean);
        self::assertSame('04006381333931', (string) $gtin14);
        self::assertSame(GtinFormat::Gtin13, $ean->format());
        self::assertSame(GtinFormat::Gtin14, $gtin14->format());
        self::assertSame('04006381333931', $ean->toGtin14());
    }

    public function testUpcAIsZeroPaddedToTheSameGtin14(): void
    {
        $upc = new Gtin('036000291452');

        self::assertSame(GtinFormat::Gtin12, $upc->format());
        self::assertSame('00036000291452', $upc->toGtin14());
        self::assertTrue($upc->equals(new Gtin('0036000291452')));
        self::assertTrue($upc->equals(new Gtin('00036000291452')));
    }

    public function testDifferentGtinsAreNotEqual(): void
    {
        self::assertFalse(new Gtin('4006381333931')->equals(new Gtin('036000291452')));
        self::assertFalse(new Gtin('4006381333931')->equals(Gln::from('0614141123452')));
    }

    public function testSeparatorsAreRefused(): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GTIN must contain digits only.');

        new Gtin('4006 81333931');
    }

    public function testEqualGtinsDecodeToTheSameGs1Prefix(): void
    {
        foreach (['96385074', '00000096385074', '0000096385074'] as $form) {
            self::assertSame(Gs1Prefix::MemberOrganisation, new Gtin($form)->gs1Prefix(), $form);
        }

        foreach (['02345673', '00000002345673'] as $form) {
            self::assertSame(Gs1Prefix::RestrictedCirculation, new Gtin($form)->gs1Prefix(), $form);
        }

        foreach (['4006381333931', '04006381333931'] as $form) {
            self::assertSame(Gs1Prefix::MemberOrganisation, new Gtin($form)->gs1Prefix(), $form);
        }
    }

    public function testFormatLengths(): void
    {
        self::assertSame(8, GtinFormat::Gtin8->length());
        self::assertSame(12, GtinFormat::Gtin12->length());
        self::assertSame(13, GtinFormat::Gtin13->length());
        self::assertSame(14, GtinFormat::Gtin14->length());
        self::assertSame(GtinFormat::Gtin8, new Gtin('96385074')->format());
    }

    public function testShorterFormsOfAPaddedGtin(): void
    {
        $gtin = new Gtin('00036000291452');

        self::assertSame('0036000291452', $gtin->asGtin13());
        self::assertSame('036000291452', $gtin->asGtin12());
        self::assertNull($gtin->asGtin8());

        $eight = new Gtin('00000096385074');
        self::assertSame('96385074', $eight->asGtin8());
        self::assertNull($eight->asGtin13());
        self::assertNull(new Gtin('96385074')->asGtin13());
    }

    public function testShorterFormsAreNullWhenTheLeadingDigitsAreNotZeros(): void
    {
        $gtin = new Gtin('4006381333931');

        self::assertSame('4006381333931', $gtin->asGtin13());
        self::assertNull($gtin->asGtin12());
        self::assertNull($gtin->asGtin8());
        self::assertNull(new Gtin('14006381333938')->asGtin13());
    }

    public function testFromPartsComputesTheCheckDigit(): void
    {
        self::assertSame('4006381333931', (string) Gtin::fromParts('4006381', '33393'));
        self::assertSame('036000291452', (string) Gtin::fromParts('0360002', '9145'));
        self::assertSame('96385074', (string) Gtin::fromParts('963', '8507'));
        self::assertSame('96385074', (string) Gtin::fromParts('96', '38507'));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function badParts(): iterable
    {
        yield 'prefix too short' => ['40', '063813339', 'GTIN company prefix must have 4 to 12 digits, 2 to 12 for a GTIN-8.'];
        yield 'GTIN-13 with a 3-digit prefix' => ['400', '638133393', 'GTIN company prefix must have 4 to 12 digits, 2 to 12 for a GTIN-8.'];
        yield 'GTIN-12 with a 3-digit prefix' => ['036', '00029145', 'GTIN company prefix must have 4 to 12 digits, 2 to 12 for a GTIN-8.'];
        yield 'GTIN-8 with a 1-digit prefix' => ['9', '638507', 'GTIN company prefix must have 4 to 12 digits, 2 to 12 for a GTIN-8.'];
        yield 'prefix too long' => ['4006381333931', '', 'GTIN company prefix must have 4 to 12 digits, 2 to 12 for a GTIN-8.'];
        yield 'prefix not digits' => ['40A1', '6381333', 'GTIN company prefix must have 4 to 12 digits, 2 to 12 for a GTIN-8.'];
        yield 'body of 10' => ['4006381', '333', 'GTIN company prefix and reference must have 7 or 11 or 12 digits in total.'];
        yield 'reference not digits' => ['4006381', '3339A', 'GTIN company prefix and reference must contain digits only.'];
    }

    #[DataProvider('badParts')]
    public function testFromPartsRefusesBadParts(string $prefix, string $reference, string $message): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage($message);

        Gtin::fromParts($prefix, $reference);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalid(): iterable
    {
        yield 'wrong check digit' => ['4006381333932', 'GTIN has a wrong check digit.'];
        yield 'length 9' => ['400638133', 'GTIN must have 8, 12, 13 or 14 digits.'];
        yield 'length 15' => ['400638133393100', 'GTIN must have 8, 12, 13 or 14 digits.'];
        yield 'empty' => ['', 'GTIN must have 8, 12, 13 or 14 digits.'];
        yield 'letters' => ['40063813339A1', 'GTIN must contain digits only.'];
    }

    #[DataProvider('invalid')]
    public function testRefusesInvalidValuesWithoutEchoingThem(string $value, string $message): void
    {
        try {
            new Gtin($value);
            self::fail('Expected an exception.');
        } catch (InvalidIdentificationKeyException $e) {
            self::assertSame($message, $e->getMessage());
        }
    }

    public function testTryFromAndIsValid(): void
    {
        self::assertNull(Gtin::tryFrom('4006381333932'));
        self::assertTrue(Gtin::isValid('4006381333931'));
    }

    /**
     * @return iterable<string, array{string, Gs1Prefix}>
     */
    public static function prefixes(): iterable
    {
        yield 'GTIN-13 ISBN' => ['9780306406157', Gs1Prefix::Isbn];
        yield 'GTIN-13 member organisation' => ['4006381333931', Gs1Prefix::MemberOrganisation];
        yield 'GTIN-12 reads the zero-padded U.P.C.' => ['036000291452', Gs1Prefix::MemberOrganisation];
        yield 'GTIN-14 skips the indicator digit' => ['19780306406154', Gs1Prefix::Isbn];
        yield 'GTIN-12 starting with 4 is a U.P.C. that reads as 04' => ['412345678910', Gs1Prefix::RestrictedCirculation];
        yield 'GTIN-8 03 is restricted by Table 1-5' => ['03456781', Gs1Prefix::RestrictedCirculation];
        yield 'GTIN-8 restricted' => ['02345673', Gs1Prefix::RestrictedCirculation];
        yield 'GTIN-8 member organisation' => ['96385074', Gs1Prefix::MemberOrganisation];
        yield 'GTIN-14 indicator 1 over a GTIN-8 reads the GS1-8 Prefix' => ['10000012345677', Gs1Prefix::MemberOrganisation];
        yield 'GTIN-14 indicator 1 over a demonstration GTIN-8' => ['10000095212347', Gs1Prefix::Demonstration];
        yield 'GTIN-14 indicator 8 over a restricted GTIN-8' => ['80000002345679', Gs1Prefix::RestrictedCirculation];
    }

    #[DataProvider('prefixes')]
    public function testGs1Prefix(string $value, Gs1Prefix $expected): void
    {
        self::assertSame($expected, new Gtin($value)->gs1Prefix());
    }
}
