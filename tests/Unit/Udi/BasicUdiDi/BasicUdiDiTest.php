<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Udi\BasicUdiDi;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaMod97;
use MSSTC4PHP\ValueObject\Gs1\Internal\Iso7064Mod37;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gmn;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\BasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\BasicUdiDiFactory;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\Gs1BasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\HibccBasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\IccbbaBasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\IfaBasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BasicUdiDiFactory::class)]
#[CoversClass(Gs1BasicUdiDi::class)]
#[CoversClass(HibccBasicUdiDi::class)]
#[CoversClass(IccbbaBasicUdiDi::class)]
#[CoversClass(IfaBasicUdiDi::class)]
#[CoversClass(Iso7064Mod37::class)]
#[CoversClass(IfaMod97::class)]
#[UsesClass(Gmn::class)]
#[UsesClass(GmnCheckCharacters::class)]
#[UsesClass(Gs1Charsets::class)]
#[UsesClass(KeyRules::class)]
final class BasicUdiDiTest extends TestCase
{
    /**
     * The examples of each issuing entity's own publication.
     *
     * @return iterable<string, array{string, class-string<BasicUdiDi>, IssuingAgency}>
     */
    public static function examples(): iterable
    {
        yield 'GS1 GMN, GenSpecs Figure 7-15' => ['1987654Ad4X4bL5ttr2310c2K', Gs1BasicUdiDi::class, IssuingAgency::Gs1];
        yield 'HIBCC' => ['++A999MODELIDENTIFIER11S8', HibccBasicUdiDi::class, IssuingAgency::Hibcc];
        yield 'ICCBBA, ST-017 Appendix 1' => ['A9990ABC123T048008', IccbbaBasicUdiDi::class, IssuingAgency::Iccbba];
        yield 'IFA' => ['PP12345ABCD.12345678.9004', IfaBasicUdiDi::class, IssuingAgency::Ifa];
    }

    /**
     * @param class-string<BasicUdiDi> $class
     */
    #[DataProvider('examples')]
    public function testTheFactoryFindsTheIssuingEntity(string $value, string $class, IssuingAgency $entity): void
    {
        $basicUdiDi = BasicUdiDiFactory::fromString($value);

        self::assertInstanceOf($class, $basicUdiDi);
        self::assertSame($value, (string) $basicUdiDi);
        self::assertSame($entity, $basicUdiDi->issuingEntity());
    }

    public function testAccessors(): void
    {
        self::assertSame('1987654Ad4X4bL5ttr2310c2K', (string) Gs1BasicUdiDi::from('1987654Ad4X4bL5ttr2310c2K')->gmn());

        $hibcc = HibccBasicUdiDi::from('++A999MODELIDENTIFIER11S8');
        self::assertSame('A999', $hibcc->labelerIdentificationCode());
        self::assertSame('MODELIDENTIFIER11', $hibcc->globalModelIdentifier());

        $iccbba = IccbbaBasicUdiDi::from('A9990ABC123T048008');
        self::assertSame('A9990', $iccbba->facilityIdentificationNumber());
        self::assertSame('ABC123', $iccbba->facilityProductCode());
        self::assertSame('T0480', $iccbba->productDescriptionCode());

        $ifa = IfaBasicUdiDi::from('PP12345ABCD.12345678.9004');
        self::assertSame('12345', $ifa->manufacturerCode());
        self::assertSame('ABCD.12345678.90', $ifa->deviceGroupCode());
    }

    public function testCheckCharacterAlgorithmsOnTheirExamples(): void
    {
        self::assertSame('08', Iso7064Mod37::compute('A9990ABC123T0480'));
        self::assertSame('04', IfaMod97::compute('PP12345ABCD.12345678.90'));
        // IFA's PPN example: PRA code 11 and PZN 03752864.
        self::assertSame('14', IfaMod97::compute('1103752864'));
        self::assertSame('S8', GmnCheckCharacters::compute('++A999MODELIDENTIFIER11'));
        // A zero sum: (38 - 0) mod 37.
        self::assertSame('01', Iso7064Mod37::compute('0000000000000000'));
    }

    /**
     * @return iterable<string, array{class-string<Iso7064Mod37|IfaMod97>, string}>
     */
    public static function badHelperInput(): iterable
    {
        yield 'MOD 37-2 empty' => [Iso7064Mod37::class, ''];
        yield 'MOD 37-2 lower case' => [Iso7064Mod37::class, 'a9990'];
        yield 'IFA empty' => [IfaMod97::class, ''];
        yield 'IFA non-ASCII' => [IfaMod97::class, "PP\xC3\xA4"];
        yield 'IFA non-ASCII first' => [IfaMod97::class, "\xC3\xA4PP"];
    }

    /**
     * @param class-string<Iso7064Mod37|IfaMod97> $helper
     */
    #[DataProvider('badHelperInput')]
    public function testHelpersRefuseInputOutsideTheirCharacterSet(string $helper, string $data): void
    {
        $this->expectException(LogicException::class);

        $helper::compute($data);
    }

    public function testAValueValidForTwoEntitiesIsAmbiguous(): void
    {
        // Both an ICCBBA (FIN PP123) and an IFA (manufacturer 12345) Basic UDI-DI with matching check characters.
        try {
            BasicUdiDiFactory::fromString('PP1234500000007005');
            self::fail('A value of two issuing entities must be ambiguous.');
        } catch (AmbiguousValueException $exception) {
            self::assertSame([IccbbaBasicUdiDi::class, IfaBasicUdiDi::class], $exception->candidates());
            self::assertSame('Ambiguous Basic UDI-DI: the value fits IccbbaBasicUdiDi, IfaBasicUdiDi.', $exception->getMessage());
        }
    }

    public function testAValueOfNoEntityIsRefused(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('Basic UDI-DI fits no issuing entity (GS1, HIBCC, ICCBBA, IFA).');

        BasicUdiDiFactory::fromString('++A999MODELIDENTIFIER11S9');
    }

    /**
     * @return iterable<string, array{class-string<BasicUdiDi>, string, string}>
     */
    public static function badValues(): iterable
    {
        $hibccFormat = 'HIBC Basic UDI-DI must be "++", a 4-character LIC starting with a letter, 1 to 17 alphanumeric characters and two check characters.';
        $iccbbaFormat = 'ICCBBA Basic UDI-DI must be a 16-character ISBT 128 UDI-DI and two checksum digits.';
        $ifaFormat = 'IFA Basic UDI-DI must be "PP", a 5-character manufacturer code, a 1 to 16 character device group code and two check digits.';

        yield 'GS1 wrong pair' => [Gs1BasicUdiDi::class, '1987654Ad4X4bL5ttr2310c2L', 'GS1 Basic UDI-DI is not a valid GMN: GMN has wrong check characters.'];
        yield 'HIBCC wrong pair' => [HibccBasicUdiDi::class, '++A999MODELIDENTIFIER11S9', 'HIBC Basic UDI-DI has wrong check characters.'];
        yield 'HIBCC one plus' => [HibccBasicUdiDi::class, '+A999MODELIDENTIFIER11S8', $hibccFormat];
        yield 'HIBCC LIC with a digit first' => [HibccBasicUdiDi::class, '++9999MODEL' . GmnCheckCharacters::compute('++9999MODEL'), $hibccFormat];
        yield 'HIBCC model of 18' => [HibccBasicUdiDi::class, '++A999' . \str_repeat('M', 18) . 'S8', $hibccFormat];
        yield 'HIBCC model with a hyphen' => [HibccBasicUdiDi::class, '++A999MO-DEL' . GmnCheckCharacters::compute('++A999MO-DEL'), $hibccFormat];
        yield 'HIBCC check character 1' => [HibccBasicUdiDi::class, '++A999MODEL1S', $hibccFormat];
        yield 'ICCBBA wrong checksum' => [IccbbaBasicUdiDi::class, 'A9990ABC123T048009', 'ICCBBA Basic UDI-DI has a wrong MOD 37-2 checksum.'];
        yield 'ICCBBA FIN with O' => [IccbbaBasicUdiDi::class, 'O9990ABC123T048008', $iccbbaFormat];
        yield 'ICCBBA 17' => [IccbbaBasicUdiDi::class, 'A9990ABC123T04808', $iccbbaFormat];
        yield 'ICCBBA letter checksum' => [IccbbaBasicUdiDi::class, 'A9990ABC123T0480AB', $iccbbaFormat];
        yield 'IFA wrong check digits' => [IfaBasicUdiDi::class, 'PP12345ABCD.12345678.9005', 'IFA Basic UDI-DI has wrong check digits.'];
        yield 'IFA other agency code' => [IfaBasicUdiDi::class, 'PQ12345ABCD.12345678.9004', $ifaFormat];
        yield 'IFA device group of 17' => [IfaBasicUdiDi::class, 'PP12345' . \str_repeat('A', 17) . '00', $ifaFormat];
        yield 'IFA lower case' => [IfaBasicUdiDi::class, 'PP12345abcd04', $ifaFormat];
        yield 'IFA manufacturer of 4' => [IfaBasicUdiDi::class, 'PP1234', $ifaFormat];
    }

    /**
     * @param class-string<BasicUdiDi> $class
     */
    #[DataProvider('badValues')]
    public function testMalformedBasicUdiDisAreRefused(string $class, string $value, string $message): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage($message);

        $class::from($value);
    }

    public function testTheGs1FailureKeepsItsCause(): void
    {
        try {
            Gs1BasicUdiDi::from('1987654Ad4X4bL5ttr2310c2L');
            self::fail('A wrong GMN must be refused.');
        } catch (InvalidUdiException $exception) {
            self::assertInstanceOf(InvalidIdentificationKeyException::class, $exception->getPrevious());
        }
    }

    public function testLongestValues(): void
    {
        $hibcc = '++A999' . \str_repeat('m', 17);
        $ifa = 'PP12345' . \str_repeat('9', 16);

        self::assertSame(25, \strlen((string) HibccBasicUdiDi::from($hibcc . GmnCheckCharacters::compute($hibcc))));
        self::assertSame(25, \strlen((string) IfaBasicUdiDi::from($ifa . IfaMod97::compute($ifa))));
        self::assertSame('ABCDE', IfaBasicUdiDi::from('PPABCDE1' . IfaMod97::compute('PPABCDE1'))->manufacturerCode());
    }
}
