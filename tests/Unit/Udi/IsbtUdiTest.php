<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Udi;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Internal\IsbtData;
use MSSTC4PHP\ValueObject\Gs1\Internal\IsbtSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\UdiDates;
use MSSTC4PHP\ValueObject\Gs1\Udi\IsbtUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsbtUdi::class)]
#[CoversClass(IsbtSyntax::class)]
#[CoversClass(IsbtData::class)]
#[CoversClass(UdiDates::class)]
final class IsbtUdiTest extends TestCase
{
    /** ICCBBA ST-017, chapter 5, Figure 6. */
    private const string FIGURE_6 = '=+06000=/A9999XYZ100T0476=,000025=A99971712345600=>019032=}017032&,1000000000000XYZ123';

    /** ICCBBA ST-017, section 4.3.1 and Figure 10. */
    private const string HRI = '=/A9997XYZ100T0479=A99991712345600=,000012=>019031';

    public function testTheCompoundMessageOfChapter5(): void
    {
        $udi = IsbtUdi::from(self::FIGURE_6);

        self::assertSame(self::FIGURE_6, (string) $udi);
        self::assertTrue($udi->equals(IsbtUdi::from('=/A9999XYZ100T0476=,000025=A99971712345600=>019032=}017032&,1000000000000XYZ123')));
        self::assertSame(IssuingAgency::Iccbba, $udi->issuingAgency());
        self::assertSame('A9999XYZ100T0476', $udi->deviceIdentifier());
        self::assertSame('A9999', $udi->facilityIdentificationNumber());
        self::assertSame('XYZ100', $udi->facilityProductCode());
        self::assertSame('T0476', $udi->productDescriptionCode());
        self::assertSame('A999717123456', $udi->donationIdentificationNumber());
        self::assertSame('000025', $udi->serial());
        self::assertSame('2019-02-01 00:00:00 UTC', $udi->expiryDate()?->format('Y-m-d H:i:s T'));
        self::assertSame('2017-02-01', $udi->productionDate()?->format('Y-m-d'));
        self::assertSame('000000000000XYZ123', $udi->lot());
        self::assertSame(self::FIGURE_6, $udi->toCompoundMessage());
    }

    public function testUnspecifiedAndSpecifiedSequencesAndTheHumanReadableFormAreOneUdi(): void
    {
        $unspecified = IsbtUdi::from('=+04000' . self::HRI);
        $specified = IsbtUdi::from('=+04035=/A9997XYZ100T0479=,000012=A99991712345600=>019031');
        $readable = IsbtUdi::from(self::HRI);

        self::assertTrue($unspecified->equals($readable));
        self::assertTrue($specified->equals(IsbtUdi::from('=/A9997XYZ100T0479=,000012=A99991712345600=>019031')));
        self::assertFalse($specified->equals($readable));
        self::assertSame('2019-01-31', $specified->expiryDate()?->format('Y-m-d'));
        self::assertSame('000012', $specified->serial());
        self::assertSame('=+04000' . self::HRI, $readable->toCompoundMessage());
        self::assertNull($readable->productionDate());
        self::assertNull($readable->lot());
    }

    public function testNonUdiDataStructuresAfterTheProductionIdentifiersAreKept(): void
    {
        $udi = IsbtUdi::from('=+05000' . self::HRI . '=%5100');

        self::assertSame('=+05000' . self::HRI . '=%5100', (string) $udi);
        self::assertSame('=+05000' . self::HRI . '=%5100', $udi->toCompoundMessage());
    }

    public function testTheCompoundMessageHeaderIsKeptSoTheSpecifiedSequenceRoundTrips(): void
    {
        $specified = '=+04035=/A9997XYZ100T0479=,000012=A99991712345600=>019031';
        $udi = IsbtUdi::from($specified);

        self::assertSame($specified, (string) $udi);
        self::assertSame($specified, $udi->toCompoundMessage());
        self::assertSame($specified, (string) IsbtUdi::from((string) $udi));
        self::assertEquals($udi, \unserialize(\serialize($udi)));
        self::assertSame(self::HRI, (string) IsbtUdi::from(self::HRI));
    }

    public function testTheLimits(): void
    {
        $longest = self::HRI . '=%' . \str_repeat('9', 3116 - \strlen(self::HRI) - 2);
        $most = self::HRI . \str_repeat('=%1', 95);

        self::assertSame(3116, \strlen((string) IsbtUdi::from($longest)));
        self::assertSame('=+99000' . $most, IsbtUdi::from($most)->toCompoundMessage());
    }

    public function testA0xPayloadStillLoads(): void
    {
        // vo-gs1 0.1 stored the data structures alone, also for compound-message input.
        $payload = 'O:37:"MSSTC4PHP\\ValueObject\\Gs1\\Udi\\IsbtUdi":1:{s:5:"value";s:50:"=/A9997XYZ100T0479=A99991712345600=,000012=>019031";}';
        $udi = \unserialize($payload);

        self::assertInstanceOf(IsbtUdi::class, $udi);
        self::assertSame(self::HRI, (string) $udi);
        self::assertTrue($udi->equals(IsbtUdi::from('=+04000' . self::HRI)));
    }

    public function testTheCompoundMessageOfTheLongestValueReadsBack(): void
    {
        $longest = IsbtUdi::from(self::HRI . '=%' . \str_repeat('9', 3116 - \strlen(self::HRI) - 2));
        $message = $longest->toCompoundMessage();

        self::assertSame(IsbtUdi::MAX_LENGTH, \strlen($message));
        self::assertSame($message, (string) IsbtUdi::from($message));
        self::assertTrue($longest->equals(IsbtUdi::from($message)));
    }

    public function testDataStructuresOf3117CharactersAreRefused(): void
    {
        $value = self::HRI . '=%' . \str_repeat('9', 3117 - \strlen(self::HRI) - 2);
        self::assertSame(3117, \strlen($value));

        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('must have at most 3116 characters, 3123 with a compound message header');
        IsbtUdi::from($value);
    }

    public function testAHeaderDoesNotLengthenTheDataStructures(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('must have at most 3116 characters, 3123 with a compound message header');
        IsbtUdi::from('=+04000' . self::HRI . '=%' . \str_repeat('9', 3117 - \strlen(self::HRI) - 2));
    }

    public function testSpecifiedSequencesOfTable3(): void
    {
        $base = '=/A9997XYZ100T0479=,000012=A99991712345600';

        self::assertSame('A9997XYZ100T0479', IsbtUdi::from('=+03034' . $base)->deviceIdentifier());
        self::assertSame('2017-02-01', IsbtUdi::from('=+04036' . $base . '=}017032')->productionDate()?->format('Y-m-d'));
        self::assertSame('2017-02-01', IsbtUdi::from('=+05037' . $base . '=>019031=}017032')->productionDate()?->format('Y-m-d'));
        self::assertSame('A9997XYZ100T0479', IsbtUdi::from('=+04123' . self::HRI)->deviceIdentifier());
    }

    public function testLeapDaysOfThreeDigitYears(): void
    {
        self::assertSame('2024-12-31', IsbtUdi::from(self::HRI . '=}024366')->productionDate()?->format('Y-m-d'));
        self::assertSame('2124-01-01', IsbtUdi::from('=/A9997XYZ100T0479=A99991712345600=,000012=>124001')->expiryDate()?->format('Y-m-d'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function badUdis(): iterable
    {
        $hri = self::HRI;

        yield 'too long' => [$hri . '=%' . \str_repeat('9', 3068), 'must have at most 3116 characters, 3123 with a compound message header'];
        yield 'too long with a header' => ['=+04000' . $hri . '=%' . \str_repeat('9', 3068), 'must have at most 3116 characters, 3123 with a compound message header'];
        yield 'too many structures' => [$hri . \str_repeat('=%1', 96), 'must have at most 99 data structures'];
        yield 'no DI first' => ['=A99991712345600=/A9997XYZ100T0479=,000012', 'must begin with the Processor Product Identification Code "=/"'];
        yield 'compound without DI first' => ['=+03000=,000012=/A9997XYZ100T0479=A99991712345600', 'must begin with the Processor Product Identification Code "=/"'];
        yield 'empty' => ['', 'must begin with the Processor Product Identification Code "=/"'];
        yield 'header only' => ['=+00000', 'must begin with the Processor Product Identification Code "=/"'];
        yield 'count too high' => ['=+05000' . $hri, 'compound message data structure count does not match'];
        yield 'count too low' => ['=+03000' . $hri, 'compound message data structure count does not match'];
        yield 'specified sequence out of order' => ['=+04035' . $hri, 'data structures are not in the order of the specified sequence'];
        yield 'specified sequence 034 with an extra' => ['=+04034=/A9997XYZ100T0479=,000012=A99991712345600=>019031', 'data structures are not in the order of the specified sequence'];
        yield 'header inside' => [$hri . '=+01000', 'has a compound message header after its start'];
        yield 'no DIN' => ['=/A9997XYZ100T0479=,000012=>019031', 'must carry the Processor Product Identification Code, the Donation Identification Number and the Product Divisions'];
        yield 'no divisions' => ['=/A9997XYZ100T0479=A99991712345600', 'must carry the Processor Product Identification Code, the Donation Identification Number and the Product Divisions'];
        yield 'repeated divisions' => [$hri . '=,000013', 'repeats a data structure'];
        yield 'repeated DI' => [$hri . '=/A9997XYZ100T0479', 'repeats a data structure'];
        yield 'UDI after non-UDI' => [$hri . '=%5100&,1LOT', 'has a UDI data structure after a non-UDI one'];
        yield 'non-UDI with a space' => [$hri . '=% 51', 'has a non-UDI data structure without data or with a character that is not printable ASCII'];
        yield 'bare data identifier' => [$hri . '&', 'has a non-UDI data structure without data or with a character that is not printable ASCII'];
        yield 'non-UDI without data' => [$hri . '=%', 'has a non-UDI data structure without data or with a character that is not printable ASCII'];
        yield 'non-UDI with a trailing newline' => [$hri . "=%5\n", 'has a non-UDI data structure without data or with a character that is not printable ASCII'];
        yield 'DI with O in the FIN' => ['=/O9997XYZ100T0479=A99991712345600=,000012', 'has a data structure with a wrong length or character'];
        yield 'DI of 15' => ['=/A9997XYZ100T047=A99991712345600=,000012', 'has a data structure with a wrong length or character'];
        yield 'DI lower case' => ['=/a9997XYZ100T0479=A99991712345600=,000012', 'has a data structure with a wrong length or character'];
        yield 'DIN of 14' => ['=/A9997XYZ100T0479=A999917123456=,000012', 'has a data structure with a wrong length or character'];
        yield 'DIN flag I' => ['=/A9997XYZ100T0479=A999917123456I0=,000012', 'has a data structure with a wrong length or character'];
        yield 'DIN letter in the year' => ['=/A9997XYZ100T0479=A9999A712345600=,000012', 'has a data structure with a wrong length or character'];
        yield 'divisions of 5' => ['=/A9997XYZ100T0479=A99991712345600=,00012', 'has a data structure with a wrong length or character'];
        yield 'expiry letters' => [$hri . '=}01903A', 'has a data structure with a wrong length or character'];
        yield 'expiry day 0' => ['=/A9997XYZ100T0479=A99991712345600=,000012=>019000', 'has a date that is not a day of its year'];
        yield 'production day 366 of 2019' => [$hri . '=}019366', 'has a date that is not a day of its year'];
        yield 'lot of 19' => [$hri . '&,1' . \str_repeat('L', 19), 'has a data structure with a wrong length or character'];
        yield 'lot empty' => [$hri . '&,1', 'has a data structure with a wrong length or character'];
    }

    #[DataProvider('badUdis')]
    public function testMalformedIsbtUdisAreRefused(string $value, string $reason): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('ISBT 128 UDI ' . $reason . '.');

        IsbtUdi::from($value);
    }
}
