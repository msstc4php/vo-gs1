<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Key;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gcn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gdti;
use MSSTC4PHP\ValueObject\Gs1\Key\Gln;
use MSSTC4PHP\ValueObject\Gs1\Key\Grai;
use MSSTC4PHP\ValueObject\Gs1\Key\Gs1Prefix;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsin;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsrn;
use MSSTC4PHP\ValueObject\Gs1\Key\Sscc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gln::class)]
#[CoversClass(Sscc::class)]
#[CoversClass(Gsrn::class)]
#[CoversClass(Gsin::class)]
#[CoversClass(Grai::class)]
#[CoversClass(Gdti::class)]
#[CoversClass(Gcn::class)]
#[CoversClass(KeyRules::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(GmnCheckCharacters::class)]
#[UsesClass(Gs1Prefix::class)]
final class NumericKeysTest extends TestCase
{
    public function testGlnFromTheGeneralSpecificationsExample(): void
    {
        self::assertSame('9521234500018', (string) Gln::fromParts('9521234', '50001'));
    }

    public function testGlnFromPartsAndGs1Prefix(): void
    {
        $gln = Gln::fromParts('0614141', '12345');

        self::assertSame('0614141123452', (string) $gln);
        self::assertSame(Gs1Prefix::MemberOrganisation, $gln->gs1Prefix());
        self::assertSame(Gs1Prefix::RestrictedCirculation, Gln::fromParts('2012345', '67890')->gs1Prefix());
    }

    public function testSsccFromTheGeneralSpecificationsExample(): void
    {
        $sscc = Sscc::fromParts('7610425', '002123456', '3');

        self::assertSame('376104250021234569', (string) $sscc);
        self::assertSame('3', $sscc->extensionDigit());
        self::assertSame(Gs1Prefix::MemberOrganisation, $sscc->gs1Prefix());
        self::assertSame('0', Sscc::fromParts('0614141', '123456789')->extensionDigit());
        self::assertSame('095212340005678903', (string) Sscc::fromParts('9521234', '000567890'));
    }

    public function testSsccPrefixIsReadAfterTheExtensionDigit(): void
    {
        self::assertSame(Gs1Prefix::Isbn, Sscc::fromParts('9780000', '123456789', '2')->gs1Prefix());
    }

    public function testSsccRefusesAnExtensionDigitThatIsNotOneDigit(): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('SSCC extension digit must have 1 digits.');

        Sscc::fromParts('7610425', '002123456', '33');
    }

    public function testGsrnAndGsinFromParts(): void
    {
        $gsrn = Gsrn::fromParts('0614141', '1234567890');
        $gsin = Gsin::fromParts('0614141', '123456789');

        self::assertSame('061414112345678902', (string) $gsrn);
        self::assertSame('06141411234567890', (string) $gsin);
        self::assertSame(Gs1Prefix::MemberOrganisation, $gsrn->gs1Prefix());
        self::assertSame(Gs1Prefix::MemberOrganisation, $gsin->gs1Prefix());
    }

    public function testGraiFromTheGeneralSpecificationsExample(): void
    {
        $grai = Grai::fromParts('9524141', '23456', '789');

        self::assertSame('09524141234564789', (string) $grai);
        self::assertSame('09524141234564', $grai->assetType());
        self::assertSame('789', $grai->serial());
    }

    public function testGraiAndItsParts(): void
    {
        $grai = Grai::fromParts('0614141', '12345', '400');

        self::assertSame('00614141123452400', (string) $grai);
        self::assertSame('00614141123452', $grai->assetType());
        self::assertSame('400', $grai->serial());
        self::assertSame(Gs1Prefix::MemberOrganisation, $grai->gs1Prefix());

        $withoutSerial = Grai::fromParts('0614141', '12345');
        self::assertSame('00614141123452', (string) $withoutSerial);
        self::assertNull($withoutSerial->serial());
        self::assertSame(Gs1Prefix::Isbn, Grai::fromParts('9780000', '12345')->gs1Prefix());
        self::assertSame(Gs1Prefix::Isbn, Grai::fromParts('9780000', '12345', 'AB')->gs1Prefix());
    }

    public function testGraiSerialTakesCset82UpTo16Characters(): void
    {
        self::assertSame('aZ!"%&\'()*+,-./0', Grai::from('00614141123452aZ!"%&\'()*+,-./0')->serial());

        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GRAI serial must have 1 to 16 characters.');

        Grai::from('00614141123452aZ!"%&\'()*+,-./09');
    }

    public function testGraiRefusesACharacterOutsideCset82(): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GRAI serial contains a character outside its GS1 character set.');

        Grai::from('00614141123452A#');
    }

    public function testGraiMustStartWithAZero(): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GRAI must start with a zero.');

        Grai::from('10614141123450');
    }

    public function testGdtiAndItsParts(): void
    {
        $gdti = Gdti::fromParts('0614141', '12345', '400');

        self::assertSame('0614141123452400', (string) $gdti);
        self::assertSame('0614141123452', $gdti->documentType());
        self::assertSame('400', $gdti->serial());
        self::assertSame(Gs1Prefix::MemberOrganisation, $gdti->gs1Prefix());
        self::assertNull(Gdti::fromParts('0614141', '12345')->serial());
    }

    public function testGdtiSerialUpTo17Characters(): void
    {
        self::assertSame('12345678901234567', Gdti::from('061414112345212345678901234567')->serial());

        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GDTI serial must have 1 to 17 characters.');

        Gdti::from('0614141123452123456789012345678');
    }

    public function testGcnAndItsSerial(): void
    {
        $gcn = Gcn::fromParts('0614141', '12345', '1234');

        self::assertSame('06141411234521234', (string) $gcn);
        self::assertSame('1234', $gcn->serial());
        self::assertSame(Gs1Prefix::MemberOrganisation, $gcn->gs1Prefix());
        self::assertNull(Gcn::fromParts('0614141', '12345')->serial());
        self::assertSame('123456789012', Gcn::from('0614141123452123456789012')->serial());
    }

    public function testGcnSerialIsAtMostTwelveDigits(): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GCN serial must have 1 to 12 digits.');

        Gcn::from('06141411234521234567890123');
    }

    public function testGcnSerialIsNumeric(): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GCN serial must contain digits only.');

        Gcn::from('06141411234521A34');
    }

    /**
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function wrongValues(): iterable
    {
        yield 'GRAI zero before the check digit' => [Grai::class, '10614141123450', 'GRAI must start with a zero.'];
        yield 'GLN check digit' => [Gln::class, '0614141123453', 'GLN has a wrong check digit.'];
        yield 'SSCC check digit' => [Sscc::class, '376104250021234560', 'SSCC has a wrong check digit.'];
        yield 'GSRN check digit' => [Gsrn::class, '061414112345678903', 'GSRN has a wrong check digit.'];
        yield 'GSIN check digit' => [Gsin::class, '06141411234567891', 'GSIN has a wrong check digit.'];
        yield 'GRAI check digit' => [Grai::class, '00614141123453400', 'GRAI has a wrong check digit.'];
        yield 'GDTI check digit' => [Gdti::class, '0614141123453400', 'GDTI has a wrong check digit.'];
        yield 'GCN check digit' => [Gcn::class, '06141411234531234', 'GCN has a wrong check digit.'];
        yield 'GLN length' => [Gln::class, '061414112345', 'GLN must have 13 digits.'];
        yield 'SSCC length' => [Sscc::class, '3761042500212345', 'SSCC must have 18 digits.'];
        yield 'GSRN length' => [Gsrn::class, '0614141123456789', 'GSRN must have 18 digits.'];
        yield 'GSIN length' => [Gsin::class, '0614141123456789', 'GSIN must have 17 digits.'];
        yield 'GLN letters' => [Gln::class, '061414112345A', 'GLN must contain digits only.'];
        yield 'GDTI short' => [Gdti::class, '061414112345', 'GDTI must have 13 digits.'];
        yield 'GCN empty' => [Gcn::class, '', 'GCN must have 13 digits.'];
        yield 'GRAI short' => [Grai::class, '0061414112345', 'GRAI must have 14 digits.'];
    }

    /**
     * @param class-string<Gln|Sscc|Gsrn|Gsin|Grai|Gdti|Gcn> $class
     */
    #[DataProvider('wrongValues')]
    public function testRefusesAWrongValueNamingTheKeyAndNothingOfTheInput(string $class, string $value, string $message): void
    {
        try {
            $class::from($value);
            self::fail('Expected an exception.');
        } catch (InvalidIdentificationKeyException $e) {
            self::assertSame($message, $e->getMessage());
        }
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function bodyLengths(): iterable
    {
        yield 'GLN' => [Gln::class, '12'];
        yield 'SSCC' => [Sscc::class, '16'];
        yield 'GSRN' => [Gsrn::class, '17'];
        yield 'GSIN' => [Gsin::class, '16'];
        yield 'GRAI' => [Grai::class, '12'];
        yield 'GDTI' => [Gdti::class, '12'];
        yield 'GCN' => [Gcn::class, '12'];
    }

    /**
     * @param class-string<Gln|Sscc|Gsrn|Gsin|Grai|Gdti|Gcn> $class
     */
    #[DataProvider('bodyLengths')]
    public function testFromPartsRefusesAWrongTotalLength(string $class, string $expected): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage(\sprintf('company prefix and reference must have %s digits in total.', $expected));

        $class::fromParts('0614141', '1234');
    }
}
