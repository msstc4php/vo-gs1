<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Udi;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Internal\HibcData;
use MSSTC4PHP\ValueObject\Gs1\Internal\HibcSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Mod43;
use MSSTC4PHP\ValueObject\Gs1\Internal\UdiDates;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcDateFormat;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HibcUdi::class)]
#[CoversClass(HibcSyntax::class)]
#[CoversClass(HibcData::class)]
#[CoversClass(UdiDates::class)]
#[CoversClass(HibcDateFormat::class)]
#[UsesClass(Mod43::class)]
final class HibcUdiTest extends TestCase
{
    /** ANSI/HIBC 2.6, section 4.3.1 and Appendix B. */
    private const string PRIMARY = '+A123BJC5D6E71G';

    public function testPrimaryDataAlone(): void
    {
        $udi = HibcUdi::from(self::PRIMARY);

        self::assertSame(self::PRIMARY, (string) $udi);
        self::assertSame(self::PRIMARY, $udi->deviceIdentifier());
        self::assertSame(IssuingAgency::Hibcc, $udi->issuingAgency());
        self::assertSame('A123', $udi->labelerIdentificationCode());
        self::assertSame('BJC5D6E7', $udi->productNumber());
        self::assertSame(1, $udi->unitOfMeasure());
        self::assertNull($udi->lot());
        self::assertNull($udi->serial());
        self::assertNull($udi->expiryDate());
        self::assertNull($udi->expiryDateFormat());
        self::assertNull($udi->manufactureDate());
        self::assertNull($udi->quantity());
        self::assertSame('H123', HibcUdi::from('+H123ABC01234567890D')->labelerIdentificationCode());
    }

    public function testPrimaryAndSecondaryConcatenatedInOneSymbol(): void
    {
        // Section 4.3.3.
        $udi = HibcUdi::from('+A123BJC5D6E71/$$52001510X3C');

        self::assertSame(self::PRIMARY, $udi->deviceIdentifier());
        self::assertSame('10X3', $udi->lot());
        self::assertSame('2020-01-15 00:00:00 UTC', $udi->expiryDate()?->format('Y-m-d H:i:s T'));
        self::assertSame(HibcDateFormat::Yyjjj, $udi->expiryDateFormat());
    }

    public function testPrimaryAndSecondarySymbolsAreJoinedThroughTheLinkCharacter(): void
    {
        // Section 4.3.2: the secondary symbol of the primary +A123BJC5D6E71G, link character G, check character D.
        $udi = HibcUdi::fromSymbols(self::PRIMARY, '+$$52001510X3GD');

        self::assertSame('+A123BJC5D6E71/$$52001510X3C', (string) $udi);
        self::assertTrue($udi->equals(HibcUdi::from('+A123BJC5D6E71/$$52001510X3C')));
        self::assertSame('A1234', HibcUdi::fromSymbols(self::PRIMARY, '+$A1234GU')->lot());
    }

    public function testSupplementalDataOfSection23(): void
    {
        $serial = HibcUdi::from('+A99912345/$$52001510X3/16D20111212/S77DEFG457');
        self::assertSame('+A99912345' . Mod43::compute('+A99912345'), $serial->deviceIdentifier());
        self::assertSame('1234', $serial->productNumber());
        self::assertSame(5, $serial->unitOfMeasure());
        self::assertSame('10X3', $serial->lot());
        self::assertSame('77DEFG45', $serial->serial());
        self::assertSame('2011-12-12', $serial->manufactureDate()?->format('Y-m-d'));
        self::assertSame('2020-01-15', $serial->expiryDate()?->format('Y-m-d'));

        $expiry = HibcUdi::from('+A99912345/$10X3/16D20111231/14D202001313');
        self::assertSame('10X3', $expiry->lot());
        self::assertSame('2011-12-31', $expiry->manufactureDate()?->format('Y-m-d'));
        self::assertSame('2020-01-31 00:00:00 UTC', $expiry->expiryDate()?->format('Y-m-d H:i:s T'));
        self::assertSame(HibcDateFormat::Yyyymmdd, $expiry->expiryDateFormat());

        $quantity = HibcUdi::from('+A99912349/$10X3/16D20111231/14D20200131/Q500Z');
        self::assertSame(9, $quantity->unitOfMeasure());
        self::assertSame(500, $quantity->quantity());
        self::assertNull($quantity->serial());
    }

    public function testSecondaryDateWinsOverSupplementalExpiryAndSerialOverS(): void
    {
        $udi = HibcUdi::from(self::concatenated('$$+3200115AB/14D20991231/SXY'));

        self::assertSame('2020-01-15', $udi->expiryDate()?->format('Y-m-d'));
        self::assertSame(HibcDateFormat::Yymmdd, $udi->expiryDateFormat());
        self::assertSame('AB', $udi->serial());
    }

    /**
     * Table F1 of Appendix F: lot 3C001, serial 0001, expiry 28 September 2005 at 22:00 (MMYY: the last day of the
     * month).
     *
     * @return iterable<string, array{string, string|null, string|null, string|null, HibcDateFormat|null}>
     */
    public static function tableF1(): iterable
    {
        yield '+ YYJJJ' => ['05271', null, null, '2005-09-28 00', HibcDateFormat::Yyjjj];
        yield '+ YYJJJ lot (Note 1)' => ['05271ABC', 'ABC', null, '2005-09-28 00', HibcDateFormat::Yyjjj];
        yield '+$ lot' => ['$3C001', '3C001', null, null, null];
        yield '+$$ MMYY' => ['$$09053C001', '3C001', null, '2005-09-30 00', HibcDateFormat::Mmyy];
        yield '+$$ 2 MMDDYY' => ['$$20928053C001', '3C001', null, '2005-09-28 00', HibcDateFormat::Mmddyy];
        yield '+$$ 3 YYMMDD' => ['$$30509283C001', '3C001', null, '2005-09-28 00', HibcDateFormat::Yymmdd];
        yield '+$$ 4 YYMMDDHH' => ['$$4050928223C001', '3C001', null, '2005-09-28 22', HibcDateFormat::Yymmddhh];
        yield '+$$ 5 YYJJJ' => ['$$5052713C001', '3C001', null, '2005-09-28 00', HibcDateFormat::Yyjjj];
        yield '+$$ 6 YYJJJHH' => ['$$605271223C001', '3C001', null, '2005-09-28 22', HibcDateFormat::Yyjjjhh];
        yield '+$$ 7' => ['$$73C001', '3C001', null, null, null];
        yield '+$+ serial' => ['$+0001', null, '0001', null, null];
        yield '+$$+ MMYY' => ['$$+09050001', null, '0001', '2005-09-30 00', HibcDateFormat::Mmyy];
        yield '+$$+ 2 MMDDYY' => ['$$+20928050001', null, '0001', '2005-09-28 00', HibcDateFormat::Mmddyy];
        yield '+$$+ 3 YYMMDD' => ['$$+30509280001', null, '0001', '2005-09-28 00', HibcDateFormat::Yymmdd];
        yield '+$$+ 4 YYMMDDHH' => ['$$+4050928220001', null, '0001', '2005-09-28 22', HibcDateFormat::Yymmddhh];
        yield '+$$+ 5 YYJJJ' => ['$$+5052710001', null, '0001', '2005-09-28 00', HibcDateFormat::Yyjjj];
        yield '+$$+ 6 YYJJJHH' => ['$$+605271220001', null, '0001', '2005-09-28 22', HibcDateFormat::Yyjjjhh];
        yield '+$$+ 7' => ['$$+70001', null, '0001', null, null];
        yield '+$$ 7 empty lot' => ['$$7', null, null, null, null];
        yield '+$$ 3 lot with . and -' => ['$$3051231A.B-1', 'A.B-1', null, '2005-12-31 00', HibcDateFormat::Yymmdd];
        yield '+$$ MMYY December' => ['$$1205', null, null, '2005-12-31 00', HibcDateFormat::Mmyy];
        yield '+$$ 5 leap day 366' => ['$$524366', null, null, '2024-12-31 00', HibcDateFormat::Yyjjj];
        yield '+$$ 2 29 February' => ['$$2022924', null, null, '2024-02-29 00', HibcDateFormat::Mmddyy];
        yield '+$$ 18-character lot' => ['$$7' . \str_repeat('L', 18), \str_repeat('L', 18), null, null, null];
    }

    #[DataProvider('tableF1')]
    public function testSecondaryDataFormatsOfTableF1(string $secondary, ?string $lot, ?string $serial, ?string $expiry, ?HibcDateFormat $format): void
    {
        $concatenated = HibcUdi::from(self::concatenated($secondary));
        $separate = HibcUdi::fromSymbols(self::PRIMARY, '+' . $secondary . 'G' . Mod43::compute('+' . $secondary . 'G'));

        self::assertTrue($concatenated->equals($separate));
        self::assertSame($lot, $concatenated->lot());
        self::assertSame($serial, $concatenated->serial());
        self::assertSame($expiry, $concatenated->expiryDate()?->format('Y-m-d H'));
        self::assertSame($expiry === null ? null : 'UTC', $concatenated->expiryDate()?->getTimezone()->getName());
        self::assertSame($format, $concatenated->expiryDateFormat());
    }

    public function testAWrongMod43CheckCharacterIsRefused(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('HIBC UDI has a wrong Mod 43 check character.');

        HibcUdi::from('+A123BJC5D6E71H');
    }

    public function testACheckCharacterMayBeASpaceOrASlash(): void
    {
        $space = self::withCheck('+A123BJC5D6E71/$$7Q', ' ');
        $slash = self::withCheck('+A123BJC5D6E71/$$7S', '/');

        self::assertStringEndsWith(' ', (string) HibcUdi::from($space));
        self::assertSame('Q', HibcUdi::from($space)->lot());
        self::assertStringEndsWith('/', (string) HibcUdi::from($slash));
        self::assertSame('S', HibcUdi::from($slash)->lot());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function badUdis(): iterable
    {
        $primary = 'primary data must be "+", a 4-character LIC starting with a letter, a 1 to 18 character product number of A-Z and 0-9, and a unit of measure digit';
        $secondary = 'secondary data must follow a format of Appendix F';
        $supplemental = 'supplemental data must be S, 16D, 14D or Q with its data';

        yield 'too long' => [\str_repeat('A', 107), 'must have at most 106 characters, got 107'];
        yield 'longest' => ['+' . \str_repeat('A', 105), 'has a wrong Mod 43 check character'];
        yield 'flag and check character only' => ['++', $primary];
        yield 'no flag' => ['A123BJC5D6E71G', 'must start with the flag character "+"'];
        yield 'empty' => ['', 'must start with the flag character "+"'];
        yield 'secondary symbol alone' => ['+$$52001510X3GD', 'is secondary data only; pass it with its primary data to HibcUdi::fromSymbols()'];
        yield 'secondary symbol with a digit' => ['+05271GX', 'is secondary data only; pass it with its primary data to HibcUdi::fromSymbols()'];
        yield 'lower case' => ['+a123BJC5D6E71G', 'must be characters of HIBCC Table B1 ending with a check character'];
        yield 'flag only' => ['+', 'must be characters of HIBCC Table B1 ending with a check character'];
        yield 'LIC of 3' => [self::withCheck('+A12'), $primary];
        yield 'LIC with a digit first' => [self::withCheck('+1ABCD1'), 'is secondary data only; pass it with its primary data to HibcUdi::fromSymbols()'];
        yield 'LIC with a special character' => [self::withCheck('+A-23BJC1'), $primary];
        yield 'no unit of measure' => [self::withCheck('+A123BJCX'), $primary];
        yield 'product number of 19' => [self::withCheck('+A123' . \str_repeat('P', 19) . '1'), $primary];
        yield 'product number with a space' => [self::withCheck('+A123BJ C1'), $primary];
        yield 'empty secondary' => [self::concatenated(''), $secondary];
        yield 'flag 8' => [self::concatenated('$$8051231'), $secondary];
        yield 'flag letter' => [self::concatenated('$$A'), $secondary];
        yield 'MMYY of 3 digits' => [self::concatenated('$$095'), $secondary];
        yield 'month 13' => [self::concatenated('$$3051301'), 'date in the YYMMDD format does not exist'];
        yield 'day 31 June' => [self::concatenated('$$2063105'), 'date in the MMDDYY format does not exist'];
        yield '29 February 2005' => [self::concatenated('$$3050229'), 'date in the YYMMDD format does not exist'];
        yield 'hour 24' => [self::concatenated('$$40509282400'), 'date in the YYMMDDHH format does not exist'];
        yield 'Julian day 366 of 2005' => [self::concatenated('$$505366'), 'date in the YYJJJ format does not exist'];
        yield 'Julian day 0' => [self::concatenated('05000'), 'date in the YYJJJ format does not exist'];
        yield 'Julian hour 24' => [self::concatenated('$$60527124'), 'date in the YYJJJHH format does not exist'];
        yield 'MMYY month 00' => [self::concatenated('$$0005'), 'date in the MMYY format does not exist'];
        yield 'legacy lot of 14' => [self::concatenated('05271' . \str_repeat('L', 14)), $secondary];
        yield 'lot of 19' => [self::concatenated('$$7' . \str_repeat('L', 19)), $secondary];
        yield 'lot only flag without lot' => [self::concatenated('$'), $secondary];
        yield 'lot starting with a dot' => [self::concatenated('$.AB'), $secondary];
        yield 'serial flag without serial' => [self::concatenated('$+'), $secondary];
        yield 'lot with a space' => [self::concatenated('$$7A B'), $secondary];
        yield 'unknown supplemental' => [self::concatenated('$$7A/X1'), $supplemental];
        yield 'empty supplemental' => [self::concatenated('$$7A/'), $supplemental];
        yield 'S of 19' => [self::concatenated('$$7A/S' . \str_repeat('S', 19)), $supplemental];
        yield '16D of 7 digits' => [self::concatenated('$$7A/16D2011121'), $supplemental];
        yield 'Q of 6 digits' => [self::concatenated('$$7A/Q123456'), $supplemental];
        yield 'Q not last' => [self::concatenated('$$7A/Q5/S1'), 'quantity must be the last supplemental data field'];
        yield 'repeated S' => [self::concatenated('$$7A/S1/S2'), 'repeats the supplemental data identifier S'];
        yield 'repeated 16D' => [self::concatenated('$$7A/16D20111212/16D20111213'), 'repeats the supplemental data identifier 16D'];
        yield 'bad 14D date' => [self::concatenated('$$7A/14D20201301'), 'date in the YYYYMMDD format does not exist'];
        yield 'bad 16D date' => [self::concatenated('$$7A/16D20210229'), 'date in the YYYYMMDD format does not exist'];
    }

    #[DataProvider('badUdis')]
    public function testMalformedHibcDataIsRefused(string $value, string $reason): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('HIBC UDI ' . $reason . '.');

        HibcUdi::from($value);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function badSymbols(): iterable
    {
        yield 'link character is not the primary check character' => [self::PRIMARY, '+$$52001510X3H' . Mod43::compute('+$$52001510X3H'), 'secondary link character does not match the primary check character'];
        yield 'wrong secondary check character' => [self::PRIMARY, '+$$52001510X3GE', 'has a wrong Mod 43 check character'];
        yield 'primary instead of secondary' => [self::PRIMARY, self::PRIMARY, 'secondary symbol must start with "+" and a digit or "$"'];
        yield 'secondary without data' => [self::PRIMARY, '+$', 'has a wrong Mod 43 check character'];
        yield 'secondary of 3' => [self::PRIMARY, '+$G', 'has a wrong Mod 43 check character'];
        yield 'secondary of 106' => [self::PRIMARY, '+$' . \str_repeat('A', 104), 'has a wrong Mod 43 check character'];
        yield 'secondary of 107' => [self::PRIMARY, '+$' . \str_repeat('A', 105), 'secondary symbol must start with "+" and a digit or "$"'];
        yield 'secondary in lower case' => [self::PRIMARY, '+$abGx', 'must be characters of HIBCC Table B1 ending with a check character'];
        yield 'concatenated primary' => ['+A1231/$$7AL', '+$$7ALX', 'primary symbol carries secondary data'];
        yield 'primary data ending with a slash' => ['+A1231/' . Mod43::compute('+A1231/'), '+$$7ALX', 'primary symbol carries secondary data'];
        yield 'secondary as primary' => ['+$$52001510X3GD', '+$$52001510X3GD', 'primary symbol must start with "+" and a letter'];
        yield 'primary without flag' => ['A123BJC5D6E71G', '+$$52001510X3GD', 'primary symbol must start with "+" and a letter'];
        yield 'primary too long' => ['+A123' . \str_repeat('P', 19) . '1' . Mod43::compute('+A123' . \str_repeat('P', 19) . '1'), '+$$52001510X3GD', 'primary symbol must start with "+" and a letter'];
        yield 'primary with a wrong check character' => ['+A123BJC5D6E71H', '+$$52001510X3HE', 'has a wrong Mod 43 check character'];
    }

    #[DataProvider('badSymbols')]
    public function testSeparateSymbolsAreChecked(string $primary, string $secondary, string $reason): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('HIBC UDI ' . $reason . '.');

        HibcUdi::fromSymbols($primary, $secondary);
    }

    public function testAPrimaryOf25CharactersEndingWithASlashJoinsItsSecondary(): void
    {
        $primary = '+A123PPPPPPPPPPPPPPPPA21/';
        $secondary = '+$$7AB/' . Mod43::compute('+$$7AB/');
        $udi = HibcUdi::fromSymbols($primary, $secondary);

        self::assertSame($primary, $udi->deviceIdentifier());
        self::assertSame('PPPPPPPPPPPPPPPPA2', $udi->productNumber());
        self::assertSame('AB', $udi->lot());
        self::assertSame($primary, (string) HibcUdi::from($primary));
    }

    /**
     * Year 2027 and hour 23 in every format: 23 November 2027 is day 327.
     *
     * @return iterable<string, array{string, string, HibcDateFormat}>
     */
    public static function datesOf2027(): iterable
    {
        yield 'MMYY' => ['$$1127', '2027-11-30 00', HibcDateFormat::Mmyy];
        yield 'MMDDYY' => ['$$2112327', '2027-11-23 00', HibcDateFormat::Mmddyy];
        yield 'YYMMDD' => ['$$3271123', '2027-11-23 00', HibcDateFormat::Yymmdd];
        yield 'YYMMDDHH' => ['$$427112323', '2027-11-23 23', HibcDateFormat::Yymmddhh];
        yield 'YYJJJ' => ['$$527327', '2027-11-23 00', HibcDateFormat::Yyjjj];
        yield 'YYJJJHH' => ['$$62732723', '2027-11-23 23', HibcDateFormat::Yyjjjhh];
        yield 'legacy YYJJJ' => ['27327', '2027-11-23 00', HibcDateFormat::Yyjjj];
        yield '14D' => ['$$7/14D20271123', '2027-11-23 00', HibcDateFormat::Yyyymmdd];
        yield 'day 365 of 2005' => ['$$505365', '2005-12-31 00', HibcDateFormat::Yyjjj];
    }

    #[DataProvider('datesOf2027')]
    public function testDateFieldOffsets(string $secondary, string $expiry, HibcDateFormat $format): void
    {
        $udi = HibcUdi::from(self::concatenated($secondary));

        self::assertSame($expiry, $udi->expiryDate()?->format('Y-m-d H'));
        self::assertSame($format, $udi->expiryDateFormat());
    }

    public function testDay367OfALeapYearIsRefused(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('HIBC UDI date in the YYJJJ format does not exist.');

        HibcUdi::from(self::concatenated('$$524367'));
    }

    public function testInvalidSecondaryContentFromSymbolsIsRefused(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('HIBC UDI date in the YYMMDD format does not exist.');

        HibcUdi::fromSymbols(self::PRIMARY, '+$$3051301G' . Mod43::compute('+$$3051301G'));
    }

    public function testMessagesNeverContainTheInput(): void
    {
        try {
            HibcUdi::from('+SECRETLOT1/$$7ZZTOP99Q');
            self::fail('A wrong check character must be refused.');
        } catch (InvalidUdiException $exception) {
            self::assertStringNotContainsString('SECRET', $exception->getMessage());
            self::assertStringNotContainsString('ZZTOP', $exception->getMessage());
        }
    }

    private static function concatenated(string $secondary): string
    {
        return self::withCheck('+A123BJC5D6E71/' . $secondary);
    }

    private static function withCheck(string $body, ?string $expected = null): string
    {
        $check = Mod43::compute($body);
        if ($expected !== null) {
            self::assertSame($expected, $check);
        }

        return $body . $check;
    }
}
