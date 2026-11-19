<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Epc;

use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementStringBuilder;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcPureIdentityUri;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcScheme;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcTagEncoding;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcTagUri;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidEpcUriException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\EpcSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EpcTagUri::class)]
#[CoversClass(EpcTagEncoding::class)]
#[UsesClass(EpcPureIdentityUri::class)]
#[UsesClass(EpcScheme::class)]
#[CoversClass(EpcSyntax::class)]
#[UsesClass(ElementString::class)]
#[UsesClass(ElementStringBuilder::class)]
#[UsesClass(ElementStringSyntax::class)]
#[UsesClass(AiElement::class)]
#[UsesClass(AiLinters::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(Gs1Dates::class)]
final class EpcTagUriTest extends TestCase
{
    /**
     * The EPC Tag URI examples of EPC Tag Data Standard 2.3 (Annex E) and one per encoding where it has none.
     *
     * @return array<string, array{EpcTagEncoding, non-empty-string, int<0, max>|null, int|null, non-empty-string}>
     */
    public static function encodingExamples(): array
    {
        return [
            'sgtin-96' => [EpcTagEncoding::Sgtin96, 'urn:epc:tag:sgtin-96:3.9521141.012345.4711', 3, 5, 'urn:epc:id:sgtin:9521141.012345.4711'],
            'sgtin-198' => [EpcTagEncoding::Sgtin198, 'urn:epc:tag:sgtin-198:3.95060001343.05.32a%2Fb', 3, 1, 'urn:epc:id:sgtin:95060001343.05.32a%2Fb'],
            'sscc-96' => [EpcTagEncoding::Sscc96, 'urn:epc:tag:sscc-96:0.952012.03456789123', 0, 6, 'urn:epc:id:sscc:952012.03456789123'],
            'sgln-96' => [EpcTagEncoding::Sgln96, 'urn:epc:tag:sgln-96:3.9521141.12345.5678', 3, 5, 'urn:epc:id:sgln:9521141.12345.5678'],
            'sgln-195' => [EpcTagEncoding::Sgln195, 'urn:epc:tag:sgln-195:3.9521141.12345.32a%2Fb', 3, 5, 'urn:epc:id:sgln:9521141.12345.32a%2Fb'],
            'grai-96' => [EpcTagEncoding::Grai96, 'urn:epc:tag:grai-96:3.9521141.12345.5678', 3, 5, 'urn:epc:id:grai:9521141.12345.5678'],
            'grai-170' => [EpcTagEncoding::Grai170, 'urn:epc:tag:grai-170:3.9521141.12345.32a%2Fb', 3, 5, 'urn:epc:id:grai:9521141.12345.32a%2Fb'],
            'giai-96' => [EpcTagEncoding::Giai96, 'urn:epc:tag:giai-96:3.9521141.5678', 3, 5, 'urn:epc:id:giai:9521141.5678'],
            'giai-202' => [EpcTagEncoding::Giai202, 'urn:epc:tag:giai-202:3.9521141.32a%2Fb', 3, 5, 'urn:epc:id:giai:9521141.32a%2Fb'],
            'gsrn-96' => [EpcTagEncoding::Gsrn96, 'urn:epc:tag:gsrn-96:3.9521141.1234567890', 3, 5, 'urn:epc:id:gsrn:9521141.1234567890'],
            'gsrnp-96' => [EpcTagEncoding::Gsrnp96, 'urn:epc:tag:gsrnp-96:3.9521141.1234567890', 3, 5, 'urn:epc:id:gsrnp:9521141.1234567890'],
            'gdti-96' => [EpcTagEncoding::Gdti96, 'urn:epc:tag:gdti-96:3.9521141.12345.5678', 3, 5, 'urn:epc:id:gdti:9521141.12345.5678'],
            'gdti-113' => [EpcTagEncoding::Gdti113, 'urn:epc:tag:gdti-113:3.9521141.12345.006847', 3, 5, 'urn:epc:id:gdti:9521141.12345.006847'],
            'gdti-174' => [EpcTagEncoding::Gdti174, 'urn:epc:tag:gdti-174:3.9521141.98765.ABCDefgh012345678', 3, 5, 'urn:epc:id:gdti:9521141.98765.ABCDefgh012345678'],
            'cpi-96' => [EpcTagEncoding::Cpi96, 'urn:epc:tag:cpi-96:3.9521141.98765.12345', 3, 5, 'urn:epc:id:cpi:9521141.98765.12345'],
            'cpi-var' => [EpcTagEncoding::CpiVar, 'urn:epc:tag:cpi-var:3.9521141.5PQ7%2FZ43.12345', 3, 5, 'urn:epc:id:cpi:9521141.5PQ7%2FZ43.12345'],
            'sgcn-96' => [EpcTagEncoding::Sgcn96, 'urn:epc:tag:sgcn-96:3.9521141.67890.04711', 3, 5, 'urn:epc:id:sgcn:9521141.67890.04711'],
            'itip-110' => [EpcTagEncoding::Itip110, 'urn:epc:tag:itip-110:3.9521141.012345.01.02.981', 3, 5, 'urn:epc:id:itip:9521141.012345.01.02.981'],
            'itip-212' => [EpcTagEncoding::Itip212, 'urn:epc:tag:itip-212:3.9521141.012345.01.02.mw133', 3, 5, 'urn:epc:id:itip:9521141.012345.01.02.mw133'],
            'gid-96' => [EpcTagEncoding::Gid96, 'urn:epc:tag:gid-96:952056.2718.1414', null, null, 'urn:epc:id:gid:952056.2718.1414'],
            'usdod-96' => [EpcTagEncoding::Usdod96, 'urn:epc:tag:usdod-96:3.CAGEY.5678', 3, null, 'urn:epc:id:usdod:CAGEY.5678'],
            'adi-var' => [EpcTagEncoding::AdiVar, 'urn:epc:tag:adi-var:3.35962.PQ7VZ4.M37GXB92', 3, null, 'urn:epc:id:adi:35962.PQ7VZ4.M37GXB92'],
        ];
    }

    public function testEveryEncodingHasAnExample(): void
    {
        $covered = \array_map(static fn (array $example): EpcTagEncoding => $example[0], \array_values(self::encodingExamples()));

        self::assertSame(EpcTagEncoding::cases(), $covered);
    }

    /**
     * @param int<0, max>|null $filter
     */
    #[DataProvider('encodingExamples')]
    public function testReadsTheExamples(EpcTagEncoding $encoding, string $uri, ?int $filter, ?int $partition, string $pure): void
    {
        $tag = EpcTagUri::from($uri);

        self::assertSame($uri, (string) $tag);
        self::assertSame($encoding, $tag->encoding());
        self::assertSame($encoding->scheme(), $tag->scheme());
        self::assertSame($filter, $tag->filter());
        self::assertSame($partition, $tag->partition());
        self::assertSame($pure, (string) $tag->toPureIdentityUri());
        self::assertSame($uri, (string) EpcTagUri::fromPureIdentityUri(EpcPureIdentityUri::from($pure), $encoding, $filter));
    }

    public function testKeepsControlFieldsAndDropsThemFromThePureIdentity(): void
    {
        $tag = EpcTagUri::from('urn:epc:tag:[att=x01][umi=1][xpc=x0004]:sgtin-96:3.9521141.112345.400');

        self::assertSame('urn:epc:id:sgtin:9521141.112345.400', (string) $tag->toPureIdentityUri());
        self::assertSame(3, $tag->filter());
        self::assertSame('(01)19521141123451(21)400', (string) $tag->toPureIdentityUri()->toElementString());
    }

    public function testPartitionFollowsTheCompanyPrefixLength(): void
    {
        self::assertSame(0, EpcTagUri::from('urn:epc:tag:sgtin-96:1.952656789012.0.4711')->partition());
        self::assertSame(6, EpcTagUri::from('urn:epc:tag:sgtin-96:1.952114.1112345.4711')->partition());
        self::assertSame(4, EpcTagUri::from('urn:epc:tag:sgtin-96:1.00000950.01093.1')->partition());
    }

    public function testFilterRangesFollowTheFilterWidth(): void
    {
        self::assertSame(7, EpcTagUri::from('urn:epc:tag:sgtin-96:7.9521141.012345.4711')->filter());
        self::assertSame(63, EpcTagUri::from('urn:epc:tag:adi-var:63.35962.PQ7VZ4.M37GXB92')->filter());
        self::assertSame(15, EpcTagUri::from('urn:epc:tag:usdod-96:15.CAGEY.5678')->filter());
    }

    public function testNumericLimitsAtTheirEdges(): void
    {
        self::assertSame('urn:epc:tag:sgtin-96:3.9521141.012345.274877906943', (string) EpcTagUri::from('urn:epc:tag:sgtin-96:3.9521141.012345.274877906943'));
        self::assertSame(0, EpcTagUri::from('urn:epc:tag:giai-96:0.952656789012.4398046511103')->partition());
        self::assertSame(6, EpcTagUri::from('urn:epc:tag:giai-96:0.952114.4611686018427387903')->partition());
        self::assertSame(6, EpcTagUri::from('urn:epc:tag:cpi-96:0.952114.999999999.2147483647')->partition());
        self::assertSame(0, EpcTagUri::from('urn:epc:tag:cpi-96:0.952114123456.999.1')->partition());
        self::assertSame('urn:epc:id:cpi:9521141.98765.0', (string) EpcTagUri::from('urn:epc:tag:cpi-96:3.9521141.98765.0')->toPureIdentityUri());
        self::assertNull(EpcTagUri::from('urn:epc:tag:gid-96:268435455.16777215.68719476735')->filter());
        self::assertSame('urn:epc:id:usdod:2S194.68719476735', (string) EpcTagUri::from('urn:epc:tag:usdod-96:3.2S194.68719476735')->toPureIdentityUri());
        $adi = 'urn:epc:tag:adi-var:0.35962.' . \str_repeat('A', 32) . '.' . \str_repeat('%2F', 30);
        self::assertSame($adi, (string) EpcTagUri::from($adi));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidUris(): iterable
    {
        yield 'pure identity' => ['urn:epc:id:sgtin:9521141.012345.4711', 'EPC Tag URI must start with urn:epc:tag: and name an encoding.'];
        yield 'raw URI' => ['urn:epc:raw:96.x0123456890ABCDEF01234567', 'EPC Tag URI must start with urn:epc:tag: and name an encoding.'];
        yield 'too long' => ['urn:epc:tag:giai-202:3.9521141.' . \str_repeat('1', 1100), 'EPC URI must have at most 1024 characters, got 1131.'];
        yield 'unknown encoding' => ['urn:epc:tag:sgtin-64:3.9521141.012345.4711', 'EPC Tag URI names an unknown encoding.'];
        yield 'encoding without tag form' => ['urn:epc:tag:ginc-96:3.9521141.1', 'EPC Tag URI names an unknown encoding.'];
        yield 'company prefix of 5' => ['urn:epc:tag:sgtin-96:3.95211.41012345.4711', 'EPC pure identity URI company prefix must have 6 to 12 digits, got 5.'];
        yield 'company prefix of 13' => ['urn:epc:tag:giai-202:3.9521141123456.1', 'EPC pure identity URI company prefix must have 6 to 12 digits, got 13.'];
        yield 'filter of 8' => ['urn:epc:tag:sgtin-96:8.9521141.012345.4711', 'EPC Tag URI filter value must be below 8.'];
        yield 'filter of 64' => ['urn:epc:tag:adi-var:64.35962.PQ7VZ4.M37GXB92', 'EPC Tag URI filter value must be below 64.'];
        yield 'filter with leading zero' => ['urn:epc:tag:sgtin-96:03.9521141.012345.4711', 'EPC Tag URI filter value must be a number without leading zeros.'];
        yield 'filter missing' => ['urn:epc:tag:sgtin-96:9521141.012345.4711', 'EPC Tag URI has no filter value.'];
        yield 'usdod filter missing' => ['urn:epc:tag:usdod-96:CAGEY.5678', 'EPC Tag URI has no filter value.'];
        yield 'giai filter missing' => ['urn:epc:tag:giai-96:952114.5678', 'EPC Tag URI has no filter value.'];
        yield 'sgcn filter missing with digits' => ['urn:epc:tag:sgcn-96:952114123456..1', 'EPC Tag URI has no filter value.'];
        yield 'filter not a number' => ['urn:epc:tag:adi-var:A.35962.PQ7VZ4.M37GXB92', 'EPC Tag URI must have a filter value before the EPC.'];
        yield 'filter on gid' => ['urn:epc:tag:gid-96:3.952056.2718.1414', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'usdod filter of 16 (4-bit field)' => ['urn:epc:tag:usdod-96:16.CAGEY.5678', 'EPC Tag URI filter value must be below 16.'];
        yield 'usdod filter of 99' => ['urn:epc:tag:usdod-96:99.2S194.1', 'EPC Tag URI filter value must be below 16.'];
        yield 'filter of 19 digits never reaches the int cast' => ['urn:epc:tag:usdod-96:1234567890123456789.CAGEY.5678', 'EPC Tag URI filter value must be below 16.'];
        yield 'three-digit filter on adi-var' => ['urn:epc:tag:adi-var:100.35962.PQ7VZ4.M37GXB92', 'EPC Tag URI filter value must be below 64.'];
        yield 'usdod serial 2^36' => ['urn:epc:tag:usdod-96:3.2S194.68719476736', 'EPC Tag URI field must be below 2^36 for usdod-96.'];
        yield 'controls out of order' => ['urn:epc:tag:[xpc=x0004][att=x01]:sgtin-96:3.9521141.012345.4711', 'EPC Tag URI control fields must be att, umi, xpc, each at most once and in that order.'];
        yield 'control repeated' => ['urn:epc:tag:[att=x01][att=x01]:sgtin-96:3.9521141.012345.4711', 'EPC Tag URI control fields must be att, umi, xpc, each at most once and in that order.'];
        yield 'control unknown' => ['urn:epc:tag:[foo=1]:sgtin-96:3.9521141.012345.4711', 'EPC Tag URI control fields must be att, umi, xpc, each at most once and in that order.'];
        yield 'control lower hex' => ['urn:epc:tag:[att=x0a]:sgtin-96:3.9521141.012345.4711', 'EPC Tag URI control fields must be att, umi, xpc, each at most once and in that order.'];
        yield 'controls without colon' => ['urn:epc:tag:[att=x01]sgtin-96:3.9521141.012345.4711', 'EPC Tag URI must start with urn:epc:tag: and name an encoding.'];
        yield 'sgtin-96 alpha serial' => ['urn:epc:tag:sgtin-96:3.9521141.012345.A1', 'EPC Tag URI field must be a number without leading zeros for sgtin-96.'];
        yield 'sgtin-96 leading zero' => ['urn:epc:tag:sgtin-96:3.9521141.012345.01', 'EPC Tag URI field must be a number without leading zeros for sgtin-96.'];
        yield 'sgtin-96 serial 2^38' => ['urn:epc:tag:sgtin-96:3.9521141.012345.274877906944', 'EPC Tag URI field must be below 2^38 for sgtin-96.'];
        yield 'sgtin-96 long serial' => ['urn:epc:tag:sgtin-96:3.9521141.012345.1000000000000', 'EPC Tag URI field must be below 2^38 for sgtin-96.'];
        yield 'sgln-96 extension 2^41' => ['urn:epc:tag:sgln-96:3.9521141.12345.2199023255552', 'EPC Tag URI field must be below 2^41 for sgln-96.'];
        yield 'grai-96 serial 2^38' => ['urn:epc:tag:grai-96:3.9521141.12345.274877906944', 'EPC Tag URI field must be below 2^38 for grai-96.'];
        yield 'gdti-96 serial 2^41' => ['urn:epc:tag:gdti-96:3.9521141.12345.2199023255552', 'EPC Tag URI field must be below 2^41 for gdti-96.'];
        yield 'gdti-113 alpha serial' => ['urn:epc:tag:gdti-113:3.9521141.12345.A1', 'EPC Tag URI field must be digits for gdti-113.'];
        yield 'itip-110 serial 2^38' => ['urn:epc:tag:itip-110:3.9521141.012345.01.02.274877906944', 'EPC Tag URI field must be below 2^38 for itip-110.'];
        yield 'giai-96 too many digits' => ['urn:epc:tag:giai-96:3.952656789012.10000000000000', 'EPC Tag URI field must have at most 13 digits for giai-96.'];
        yield 'giai-96 2^62' => ['urn:epc:tag:giai-96:3.952114.4611686018427387904', 'EPC Tag URI field must be below 2^62 for giai-96.'];
        yield 'giai-96 alpha' => ['urn:epc:tag:giai-96:3.9521141.A', 'EPC Tag URI field must be a number without leading zeros for giai-96.'];
        yield 'cpi-96 alpha reference' => ['urn:epc:tag:cpi-96:3.9521141.123ABC.1', 'EPC Tag URI field must be a number without leading zeros for cpi-96.'];
        yield 'cpi-96 long reference' => ['urn:epc:tag:cpi-96:3.952114123456.1000.1', 'EPC Tag URI field must have at most 3 digits for cpi-96.'];
        yield 'cpi-96 reference of 10 digits' => ['urn:epc:tag:cpi-96:3.952114.2147483648.1', 'EPC Tag URI field must have at most 9 digits for cpi-96.'];
        yield 'cpi-96 reference of 4 digits at partition 0' => ['urn:epc:tag:cpi-96:3.952114123456.2048.1', 'EPC Tag URI field must have at most 3 digits for cpi-96.'];
        yield 'cpi-96 serial 2^31' => ['urn:epc:tag:cpi-96:3.9521141.98765.2147483648', 'EPC Tag URI field must be below 2^31 for cpi-96.'];
        yield 'gid-96 manager 2^28' => ['urn:epc:tag:gid-96:268435456.1.1', 'EPC Tag URI field must be below 2^28 for gid-96.'];
        yield 'gid-96 class 2^24' => ['urn:epc:tag:gid-96:1.16777216.1', 'EPC Tag URI field must be below 2^24 for gid-96.'];
        yield 'gid-96 serial 2^36' => ['urn:epc:tag:gid-96:1.1.68719476736', 'EPC Tag URI field must be below 2^36 for gid-96.'];
        yield 'adi-var part of 33' => ['urn:epc:tag:adi-var:3.35962.' . \str_repeat('A', 33) . '.1', 'EPC Tag URI field must have at most 32 characters for adi-var.'];
        yield 'adi-var serial of 31' => ['urn:epc:tag:adi-var:3.35962..' . \str_repeat('1', 31), 'EPC Tag URI field must have at most 30 characters for adi-var.'];
    }

    #[DataProvider('invalidUris')]
    public function testRefuses(string $uri, string $message): void
    {
        $this->expectException(InvalidEpcUriException::class);
        $this->expectExceptionMessage($message);

        EpcTagUri::from($uri);
    }

    public function testFromPureIdentityUriChecksTheEncodingAndFilter(): void
    {
        $pure = EpcPureIdentityUri::from('urn:epc:id:sgtin:9521141.012345.A1');

        self::assertSame('urn:epc:tag:sgtin-198:0.9521141.012345.A1', (string) EpcTagUri::fromPureIdentityUri($pure, EpcTagEncoding::Sgtin198, 0));

        foreach ([[EpcTagEncoding::Sscc96, 3, 'EPC Tag URI encoding sscc-96 is not one of scheme sgtin.'], [EpcTagEncoding::Sgtin198, null, 'EPC Tag URI encoding sgtin-198 needs a filter value.'], [EpcTagEncoding::Sgtin96, 3, 'EPC Tag URI field must be a number without leading zeros for sgtin-96.']] as [$encoding, $filter, $message]) {
            try {
                EpcTagUri::fromPureIdentityUri($pure, $encoding, $filter);
                self::fail('Expected InvalidEpcUriException.');
            } catch (InvalidEpcUriException $exception) {
                self::assertSame($message, $exception->getMessage());
            }
        }

        $this->expectException(InvalidEpcUriException::class);
        $this->expectExceptionMessage('EPC Tag URI encoding gid-96 has no filter value.');

        EpcTagUri::fromPureIdentityUri(EpcPureIdentityUri::from('urn:epc:id:gid:952056.2718.1414'), EpcTagEncoding::Gid96, 0);
    }

    public function testEncodingsKnowTheirSchemeAndFilter(): void
    {
        self::assertSame(EpcScheme::Gsrnp, EpcTagEncoding::Gsrnp96->scheme());
        self::assertFalse(EpcTagEncoding::Gid96->hasFilter());
        self::assertTrue(EpcTagEncoding::Usdod96->hasFilter());
        self::assertSame(6, EpcTagEncoding::AdiVar->filterBits());
        self::assertSame(4, EpcTagEncoding::Usdod96->filterBits());
        self::assertSame(3, EpcTagEncoding::Sgtin96->filterBits());
        self::assertNull(EpcTagEncoding::Gid96->filterBits());
        self::assertTrue(EpcTagEncoding::Sgtin96->isPartitioned());
        self::assertFalse(EpcTagEncoding::AdiVar->isPartitioned());
    }

    public function testElementStringOfATag(): void
    {
        self::assertSame('(8003)09521141123454A', EpcTagUri::from('urn:epc:tag:grai-170:1.9521141.12345.A')->toPureIdentityUri()->toElementString()?->value);
        self::assertInstanceOf(ElementString::class, EpcTagUri::from('urn:epc:tag:sscc-96:0.952012.03456789123')->toPureIdentityUri()->toElementString());
    }
}
