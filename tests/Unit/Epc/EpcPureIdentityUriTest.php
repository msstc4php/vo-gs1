<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Epc;

use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementStringBuilder;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcPureIdentityUri;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcScheme;
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

#[CoversClass(EpcPureIdentityUri::class)]
#[CoversClass(EpcScheme::class)]
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
final class EpcPureIdentityUriTest extends TestCase
{
    /**
     * One example per scheme, from EPC Tag Data Standard 2.3 sections 6.3 and 7; the element string is the one
     * section 7 gives, null for the schemes without one.
     *
     * @return array<string, array{EpcScheme, non-empty-string, non-empty-string|null}>
     */
    public static function schemeExamples(): array
    {
        return [
            'sgtin' => [EpcScheme::Sgtin, 'urn:epc:id:sgtin:9521141.012345.32a%2Fb', '(01)09521141123454(21)32a/b'],
            'sscc' => [EpcScheme::Sscc, 'urn:epc:id:sscc:9521141.1234567890', '(00)195211412345678900'],
            'sgln' => [EpcScheme::Sgln, 'urn:epc:id:sgln:9521141.12345.32a%2Fb', '(414)9521141123454(254)32a/b'],
            'grai' => [EpcScheme::Grai, 'urn:epc:id:grai:9521141.12345.32a%2Fb', '(8003)0952114112345432a/b'],
            'giai' => [EpcScheme::Giai, 'urn:epc:id:giai:9521141.32a%2Fb', '(8004)952114132a/b'],
            'gsrn' => [EpcScheme::Gsrn, 'urn:epc:id:gsrn:9521141.1234567890', '(8018)952114112345678906'],
            'gsrnp' => [EpcScheme::Gsrnp, 'urn:epc:id:gsrnp:9521141.1234567890', '(8017)952114112345678906'],
            'gdti' => [EpcScheme::Gdti, 'urn:epc:id:gdti:9521141.12345.006847', '(253)9521141123454006847'],
            'cpi' => [EpcScheme::Cpi, 'urn:epc:id:cpi:9521141.5PQ7%2FZ43.12345', '(8010)95211415PQ7/Z43(8011)12345'],
            'sgcn' => [EpcScheme::Sgcn, 'urn:epc:id:sgcn:9521141.67890.04711', '(255)952114167890904711'],
            'ginc' => [EpcScheme::Ginc, 'urn:epc:id:ginc:9521141.xyz47%2F11', '(401)9521141xyz47/11'],
            'gsin' => [EpcScheme::Gsin, 'urn:epc:id:gsin:9521141.123456789', '(402)95211411234567892'],
            'itip' => [EpcScheme::Itip, 'urn:epc:id:itip:9521141.012345.04.04.32a%2Fb', '(8006)095211411234540404(21)32a/b'],
            'upui' => [EpcScheme::Upui, "urn:epc:id:upui:9521141.089456.51qIgY)%3C%26Jp3*j7'SDB", "(01)09521141894569(235)51qIgY)<&Jp3*j7'SDB"],
            'pgln' => [EpcScheme::Pgln, 'urn:epc:id:pgln:9521141.89012', '(417)9521141890127'],
            'gid' => [EpcScheme::Gid, 'urn:epc:id:gid:95100000.12345.400', null],
            'usdod' => [EpcScheme::Usdod, 'urn:epc:id:usdod:2S194.12345678901', null],
            'adi' => [EpcScheme::Adi, 'urn:epc:id:adi:W81X9C.3KL984PX1.2WMA52', null],
            'bic' => [EpcScheme::Bic, 'urn:epc:id:bic:CSQU3054383', null],
            'imovn' => [EpcScheme::Imovn, 'urn:epc:id:imovn:9176187', null],
        ];
    }

    public function testEverySchemeHasAnExample(): void
    {
        $covered = \array_map(static fn (array $example): EpcScheme => $example[0], \array_values(self::schemeExamples()));

        self::assertSame(EpcScheme::cases(), $covered);
    }

    #[DataProvider('schemeExamples')]
    public function testReadsTheExamples(EpcScheme $scheme, string $uri, ?string $elementString): void
    {
        $epc = EpcPureIdentityUri::from($uri);

        self::assertSame($uri, (string) $epc);
        self::assertSame($scheme, $epc->scheme());
        self::assertSame($elementString, $epc->toElementString()?->value);
        self::assertSame($elementString !== null, $scheme->hasElementString());
    }

    #[DataProvider('schemeExamples')]
    public function testWritesTheExamplesFromTheirElementStrings(EpcScheme $scheme, string $uri, ?string $elementString): void
    {
        if ($elementString === null) {
            $this->expectException(InvalidEpcUriException::class);
            $this->expectExceptionMessage('EPC scheme has no GS1 element string form.');
            $elementString = '(01)09521141123454(21)1';
        }

        self::assertSame($uri, (string) EpcPureIdentityUri::fromElementString(ElementString::from($elementString), $scheme, 7));
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string|null}>
     */
    public static function moreExamples(): iterable
    {
        yield 'GTIN-12 (7.3.1)' => ['urn:epc:id:sgtin:0614141.012345.1', '(01)00614141123452(21)1'];
        yield 'GTIN-8 (7.3.2)' => ['urn:epc:id:sgtin:00000950.01093.1', '(01)00000095010939(21)1'];
        yield 'individually assigned GTIN (7.2.1)' => ['urn:epc:id:sgtin:952656789012.0.4711', '(01)09526567890126(21)4711'];
        yield 'GLN without extension (7.5)' => ['urn:epc:id:sgln:9521141.12345.0', '(414)9521141123454'];
        yield 'GLN without extension (7.2.2)' => ['urn:epc:id:sgln:952656789012..0', '(414)9526567890126'];
        yield 'GLN with extension (7.2.2)' => ['urn:epc:id:sgln:952656789012..4711', '(414)9526567890126(254)4711'];
        yield 'SSCC (7.2.3)' => ['urn:epc:id:sscc:952656789012.03456', '(00)095265678901234568'];
        yield 'GIAI (7.2.3)' => ['urn:epc:id:giai:952656789012.345678901234567890', '(8004)952656789012345678901234567890'];
        yield 'serial with a dot and a colon' => ['urn:epc:id:sgtin:9521141.012345.a.b:c', '(01)09521141123454(21)a.b:c'];
        yield 'CPI' => ['urn:epc:id:cpi:9521141.123ABC.123456789', '(8010)9521141123ABC(8011)123456789'];
        yield 'CPI serial 0 (GenSpecs 3.9.11)' => ['urn:epc:id:cpi:9521141.123ABC.0', '(8010)9521141123ABC(8011)0'];
        yield 'CPI with #' => ['urn:epc:id:cpi:9521141.A%23-.1', '(8010)9521141A#-(8011)1'];
        yield 'ADI without part number' => ['urn:epc:id:adi:2S194..12345678901', null];
        yield 'ADI non-serialised part' => ['urn:epc:id:adi:2S194.A-B%2FC.%2312', null];
        yield 'DODAAC' => ['urn:epc:id:usdod:W81X9C.0', null];
    }

    #[DataProvider('moreExamples')]
    public function testReadsMoreExamples(string $uri, ?string $elementString): void
    {
        self::assertSame($elementString, EpcPureIdentityUri::from($uri)->toElementString()?->value);
    }

    public function testNormalisesTheCaseOfPercentEscapesOnly(): void
    {
        $epc = EpcPureIdentityUri::from('urn:epc:id:sgtin:9521141.012345.32a%2fb%3c');

        self::assertSame('urn:epc:id:sgtin:9521141.012345.32a%2Fb%3C', (string) $epc);
        self::assertTrue($epc->equals(EpcPureIdentityUri::from('urn:epc:id:sgtin:9521141.012345.32a%2Fb%3C')));
        self::assertFalse(EpcPureIdentityUri::from('urn:epc:id:sgtin:9521141.012345.A')->equals(EpcPureIdentityUri::from('urn:epc:id:sgtin:9521141.012345.%41')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidUris(): iterable
    {
        yield 'empty' => ['', 'EPC pure identity URI must start with urn:epc:id: and name a known scheme.'];
        yield 'not an EPC' => ['urn:isbn:9780000000002', 'EPC pure identity URI must start with urn:epc:id: and name a known scheme.'];
        yield 'upper-case prefix' => ['URN:EPC:ID:sgtin:9521141.012345.1', 'EPC pure identity URI must start with urn:epc:id: and name a known scheme.'];
        yield 'unknown scheme' => ['urn:epc:id:lgtin:9521141.012345.1', 'EPC pure identity URI must start with urn:epc:id: and name a known scheme.'];
        yield 'class URI' => ['urn:epc:class:lgtin:4012345.012345.998877', 'EPC pure identity URI must start with urn:epc:id: and name a known scheme.'];
        yield 'no body' => ['urn:epc:id:sgtin:', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'too long' => ['urn:epc:id:giai:9521141.' . \str_repeat('1', 1100), 'EPC URI must have at most 1024 characters, got 1124.'];
        yield 'SGTIN digits not 13' => ['urn:epc:id:sgtin:9521141.12345.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'SGTIN company prefix of 5' => ['urn:epc:id:sgtin:95211.41012345.1', 'EPC pure identity URI company prefix must have 6 to 12 digits, got 5.'];
        yield 'GIAI company prefix of 13' => ['urn:epc:id:giai:9521141123456.1', 'EPC pure identity URI company prefix must have 6 to 12 digits, got 13.'];
        yield 'GIAI company prefix of 5' => ['urn:epc:id:giai:95211.1', 'EPC pure identity URI company prefix must have 6 to 12 digits, got 5.'];
        yield 'SGTIN missing serial' => ['urn:epc:id:sgtin:9521141.012345.', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'SGTIN raw slash' => ['urn:epc:id:sgtin:9521141.012345.a/b', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'SGTIN bad escape' => ['urn:epc:id:sgtin:9521141.012345.a%2', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'SGTIN escape outside CSET 82' => ['urn:epc:id:sgtin:9521141.012345.a%20b', 'EPC pure identity URI does not correspond to a valid GS1 element string: Element string AI (21) data contains a character outside its GS1 character set.'];
        yield 'SGTIN serial of 21' => ['urn:epc:id:sgtin:9521141.012345.' . \str_repeat('1', 21), 'EPC pure identity URI does not correspond to a valid GS1 element string: Element string AI (21) data must have 1 to 20 characters, got 21.'];
        yield 'SSCC digits not 17' => ['urn:epc:id:sscc:9521141.123456789', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'SGLN digits not 12' => ['urn:epc:id:sgln:9521141.1234.0', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'GRAI digits not 12' => ['urn:epc:id:grai:9521141.123456.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'GSRN digits not 17' => ['urn:epc:id:gsrn:9521141.123456789', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'GDTI digits not 12' => ['urn:epc:id:gdti:9521141.1234.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'SGCN alpha serial' => ['urn:epc:id:sgcn:9521141.67890.A', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'SGCN serial of 13' => ['urn:epc:id:sgcn:9521141.67890.0123456789012', 'EPC pure identity URI does not correspond to a valid GS1 element string: Element string AI (255) data must have 13 to 25 characters, got 26.'];
        yield 'GSIN digits not 16' => ['urn:epc:id:gsin:9521141.12345678', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'ITIP piece of 1' => ['urn:epc:id:itip:9521141.012345.4.004.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'ITIP piece above total' => ['urn:epc:id:itip:9521141.012345.05.04.1', 'EPC pure identity URI does not correspond to a valid GS1 element string: Element string AI (8006) data fails the GS1 pieceoftotal check.'];
        yield 'PGLN digits not 12' => ['urn:epc:id:pgln:9521141.8901', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'CPI lower case' => ['urn:epc:id:cpi:9521141.abc.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'CPI serial leading zero' => ['urn:epc:id:cpi:9521141.ABC.01', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'GID leading zero' => ['urn:epc:id:gid:095100000.12345.400', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'GID two parts' => ['urn:epc:id:gid:95100000.12345', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'USDOD letter I' => ['urn:epc:id:usdod:2SI94.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'USDOD four characters' => ['urn:epc:id:usdod:2S19.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'ADI # inside' => ['urn:epc:id:adi:2S194..1%231', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'ADI empty serial' => ['urn:epc:id:adi:2S194..', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'ADI lower case' => ['urn:epc:id:adi:2S194.a.1', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'BIC category' => ['urn:epc:id:bic:CSQA3054383', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'BIC letter O' => ['urn:epc:id:bic:CSOU3054383', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'IMOVN 6 digits' => ['urn:epc:id:imovn:917618', 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'line break' => ["urn:epc:id:imovn:9176187\n", 'EPC pure identity URI must start with urn:epc:id: and name a known scheme.'];
    }

    #[DataProvider('invalidUris')]
    public function testRefuses(string $uri, string $message): void
    {
        $this->expectException(InvalidEpcUriException::class);
        $this->expectExceptionMessage($message);

        EpcPureIdentityUri::from($uri);
    }

    public function testTheLengthCapIsInclusive(): void
    {
        $this->expectException(InvalidEpcUriException::class);
        $this->expectExceptionMessage('EPC pure identity URI does not correspond to a valid GS1 element string');

        EpcPureIdentityUri::from('urn:epc:id:giai:9521141.' . \str_repeat('1', 1000));
    }

    public function testCompanyPrefixMessage(): void
    {
        $this->expectException(InvalidEpcUriException::class);
        $this->expectExceptionMessage('EPC pure identity URI company prefix must have 6 to 12 digits, got 5.');

        EpcPureIdentityUri::from('urn:epc:id:sgtin:95211.41012345.1');
    }

    /**
     * @return iterable<string, array{non-empty-string, EpcScheme, int, string}>
     */
    public static function unconvertibleElementStrings(): iterable
    {
        yield 'no serial' => ['(01)09521141123454', EpcScheme::Sgtin, 7, 'EPC needs AI (21) in the element string.'];
        yield 'GRAI without serial' => ['(8003)09521141123454', EpcScheme::Grai, 7, 'EPC needs a serial number in AI (8003).'];
        yield 'GDTI without serial' => ['(253)9521141123454', EpcScheme::Gdti, 7, 'EPC needs a serial number in AI (253).'];
        yield 'GCN without serial' => ['(255)9521141678909', EpcScheme::Sgcn, 7, 'EPC needs a serial number in AI (255).'];
        yield 'prefix of 5' => ['(01)09521141123454(21)1', EpcScheme::Sgtin, 5, 'EPC company prefix length must be 6 to 12, got 5.'];
        yield 'prefix of 13' => ['(01)09521141123454(21)1', EpcScheme::Sgtin, 13, 'EPC company prefix length must be 6 to 12, got 13.'];
        yield 'GIAI prefix not digits' => ['(8004)9521ABC', EpcScheme::Giai, 6, 'EPC pure identity URI does not match the grammar of its scheme.'];
        yield 'GIAI all prefix' => ['(8004)9521141', EpcScheme::Giai, 7, 'EPC pure identity URI does not match the grammar of its scheme.'];
    }

    #[DataProvider('unconvertibleElementStrings')]
    public function testFromElementStringRefuses(string $elementString, EpcScheme $scheme, int $length, string $message): void
    {
        $this->expectException(InvalidEpcUriException::class);
        $this->expectExceptionMessage($message);

        EpcPureIdentityUri::fromElementString(ElementString::from($elementString), $scheme, $length);
    }

    public function testFromElementStringEscapesAndIgnoresOtherAis(): void
    {
        self::assertSame(
            "urn:epc:id:sgtin:952114.1112345.%22%25%26%2F%3C%3E%3F!'(*+,-.:;=_",
            (string) EpcPureIdentityUri::fromElementString(ElementString::from('(01)19521141123451(17)261231(21)"%&/<>?!\'\(*+,-.:;=_'), EpcScheme::Sgtin, 6),
        );
        self::assertSame('urn:epc:id:grai:9521141.12345.A', (string) EpcPureIdentityUri::fromElementString(ElementString::from('(8003)09521141123454A'), EpcScheme::Grai, 7));
        self::assertSame('urn:epc:id:gdti:9521141.12345.X', (string) EpcPureIdentityUri::fromElementString(ElementString::from('(253)9521141123454X'), EpcScheme::Gdti, 7));
        self::assertSame('urn:epc:id:sgcn:9521141.67890.1', (string) EpcPureIdentityUri::fromElementString(ElementString::from('(255)95211416789091'), EpcScheme::Sgcn, 7));
        self::assertSame(
            'urn:epc:id:sgln:952114112345..0',
            (string) EpcPureIdentityUri::fromElementString(ElementString::from('(414)9521141123454'), EpcScheme::Sgln, 12),
        );
        self::assertSame(
            'urn:epc:id:cpi:9521141.A%2FB%23-.1',
            (string) EpcPureIdentityUri::fromElementString(ElementString::from('(8010)9521141A/B#-(8011)1'), EpcScheme::Cpi, 7),
        );
    }
}
