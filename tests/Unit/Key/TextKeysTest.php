<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Key;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Cpid;
use MSSTC4PHP\ValueObject\Gs1\Key\Giai;
use MSSTC4PHP\ValueObject\Gs1\Key\Ginc;
use MSSTC4PHP\ValueObject\Gs1\Key\Gmn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gs1Prefix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Giai::class)]
#[CoversClass(Ginc::class)]
#[CoversClass(Cpid::class)]
#[CoversClass(Gmn::class)]
#[CoversClass(Gs1Charsets::class)]
#[CoversClass(KeyRules::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(GmnCheckCharacters::class)]
#[UsesClass(Gs1Prefix::class)]
final class TextKeysTest extends TestCase
{
    public function testGiaiAcceptsTheWholeCset82(): void
    {
        $value = '0614141' . Gs1Charsets::CSET_82;

        self::assertSame(Gs1Prefix::MemberOrganisation, Giai::from('0614141ABC')->gs1Prefix());
        self::assertSame('0614141ABC', (string) Giai::fromParts('0614141', 'ABC'));
        self::assertSame('09521234500XY09', (string) Giai::fromParts('0952123', '4500XY09'));
        self::assertSame(\substr($value, 0, 30), (string) Giai::from(\substr($value, 0, 30)));
    }

    public function testGiaiRefusesACharacterOutsideCset82(): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GIAI contains a character outside its GS1 character set.');

        Giai::from('0614141 ABC');
    }

    /**
     * @return iterable<string, array{class-string<Giai|Ginc|Cpid>, string, string}>
     */
    public static function badTexts(): iterable
    {
        yield 'GIAI empty' => [Giai::class, '', 'GIAI must have 1 to 30 characters.'];
        yield 'GIAI 31' => [Giai::class, '0614141' . \str_repeat('A', 24), 'GIAI must have 1 to 30 characters.'];
        yield 'GIAI no prefix' => [Giai::class, '06A4141', 'GIAI must start with the digits of a GS1 Company Prefix.'];
        yield 'GIAI two digits' => [Giai::class, '06', 'GIAI must start with the digits of a GS1 Company Prefix.'];
        yield 'GINC no prefix' => [Ginc::class, 'AB12345', 'GINC must start with the digits of a GS1 Company Prefix.'];
        yield 'CPID no prefix' => [Cpid::class, 'AB12345', 'CPID must start with the digits of a GS1 Company Prefix.'];
        yield 'GINC 31' => [Ginc::class, '0614141' . \str_repeat('A', 24), 'GINC must have 1 to 30 characters.'];
        yield 'GINC non-ASCII' => [Ginc::class, "0614141\xC3\xA9", 'GINC contains a character outside its GS1 character set.'];
        yield 'CPID lowercase' => [Cpid::class, '0614141abc', 'CPID contains a character outside its GS1 character set.'];
        yield 'CPID outside CSET 39' => [Cpid::class, '0614141AB!', 'CPID contains a character outside its GS1 character set.'];
        yield 'CPID 31' => [Cpid::class, '0614141' . \str_repeat('A', 24), 'CPID must have 1 to 30 characters.'];
    }

    /**
     * @param class-string<Giai|Ginc|Cpid> $class
     */
    #[DataProvider('badTexts')]
    public function testRefusesBadValuesWithoutEchoingThem(string $class, string $value, string $message): void
    {
        try {
            $class::from($value);
            self::fail('Expected an exception.');
        } catch (InvalidIdentificationKeyException $e) {
            self::assertSame($message, $e->getMessage());
        }
    }

    public function testGincAndCpid(): void
    {
        self::assertSame('0614141RF1234', (string) Ginc::fromParts('0614141', 'RF1234'));
        self::assertSame(Gs1Prefix::MemberOrganisation, Ginc::from('0614141RF1234')->gs1Prefix());
        self::assertSame('0614141AB-1/2#3', (string) Cpid::from('0614141AB-1/2#3'));
        self::assertSame(Gs1Prefix::Isbn, Cpid::fromParts('9780000', 'X1')->gs1Prefix());
        self::assertSame(Gs1Prefix::Isbn, Giai::from('9780ABC')->gs1Prefix());
    }

    public function testShortZeroRunDecodesAsUnknown(): void
    {
        self::assertSame(Gs1Prefix::Unknown, Cpid::from('00000ABC')->gs1Prefix());
        self::assertSame(Gs1Prefix::Unknown, Giai::from('000000X')->gs1Prefix());
    }

    public function testCpidAcceptsTheWholeCset39(): void
    {
        foreach ([\substr(Gs1Charsets::CSET_39, 0, 26), \substr(Gs1Charsets::CSET_39, 13)] as $part) {
            self::assertSame('0614' . $part, (string) Cpid::from('0614' . $part));
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function badPrefixes(): iterable
    {
        yield 'two digits' => ['06'];
        yield 'three digits' => ['061'];
        yield 'thirteen digits' => ['0614141123456'];
        yield 'letters' => ['06A1'];
    }

    #[DataProvider('badPrefixes')]
    public function testFromPartsRefusesABadCompanyPrefix(string $prefix): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GIAI company prefix must have 4 to 12 digits.');

        Giai::fromParts($prefix, 'X');
    }

    public function testGmnFromTheGeneralSpecificationsFigure715(): void
    {
        $gmn = Gmn::from('1987654Ad4X4bL5ttr2310c2K');

        self::assertSame(Gs1Prefix::MemberOrganisation, $gmn->gs1Prefix());
        self::assertSame('1987654Ad4X4bL5ttr2310c2K', (string) Gmn::fromParts('1987654', 'Ad4X4bL5ttr2310c'));
        self::assertSame('0614141MODEL1HT', (string) Gmn::fromParts('0614141', 'MODEL1'));
    }

    public function testGmnBodyIsAtMost23Characters(): void
    {
        self::assertSame(25, \strlen((string) Gmn::fromParts('0614141', \str_repeat('A', 16))));

        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GMN company prefix and model reference must have 1 to 23 characters.');

        Gmn::fromParts('0614141', \str_repeat('A', 17));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function badGmns(): iterable
    {
        yield 'wrong check characters' => ['1987654Ad4X4bL5ttr2310c2L', 'GMN has wrong check characters.'];
        yield 'outside CSET 82' => ['1987654 d4X4bL5ttr2310c2K', 'GMN contains a character outside its GS1 character set.'];
        yield 'three prefix digits' => ['061AB78', 'GMN must start with the digits of a GS1 Company Prefix.'];
        yield 'no prefix digits' => ['AB87654', 'GMN must start with the digits of a GS1 Company Prefix.'];
        yield 'over 25' => [\str_repeat('1', 26), 'GMN must have 1 to 25 characters.'];
    }

    #[DataProvider('badGmns')]
    public function testGmnRefusesWrongValues(string $value, string $message): void
    {
        try {
            Gmn::from($value);
            self::fail('Expected an exception.');
        } catch (InvalidIdentificationKeyException $e) {
            self::assertSame($message, $e->getMessage());
        }
    }
}
