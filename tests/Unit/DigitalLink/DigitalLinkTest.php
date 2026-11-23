<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\DigitalLink;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementStringBuilder;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidDigitalLinkException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\DigitalLinkParts;
use MSSTC4PHP\ValueObject\Gs1\Internal\DigitalLinkSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Cpid;
use MSSTC4PHP\ValueObject\Gs1\Key\Gcn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gdti;
use MSSTC4PHP\ValueObject\Gs1\Key\Giai;
use MSSTC4PHP\ValueObject\Gs1\Key\Ginc;
use MSSTC4PHP\ValueObject\Gs1\Key\Gln;
use MSSTC4PHP\ValueObject\Gs1\Key\Gmn;
use MSSTC4PHP\ValueObject\Gs1\Key\Grai;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsin;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsrn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Key\IdentificationKey;
use MSSTC4PHP\ValueObject\Gs1\Key\Sscc;
use MSSTC4PHP\ValueObject\Network\Url;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DigitalLink::class)]
#[CoversClass(DigitalLinkSyntax::class)]
#[CoversClass(DigitalLinkParts::class)]
#[UsesClass(ElementString::class)]
#[UsesClass(ElementStringBuilder::class)]
#[UsesClass(ElementStringSyntax::class)]
#[UsesClass(AiElement::class)]
#[UsesClass(AiLinters::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(Gs1Charsets::class)]
#[UsesClass(Gs1Dates::class)]
#[UsesClass(GmnCheckCharacters::class)]
#[UsesClass(KeyRules::class)]
#[UsesClass(Gtin::class)]
#[UsesClass(Sscc::class)]
#[UsesClass(Gln::class)]
#[UsesClass(Giai::class)]
#[UsesClass(Gsrn::class)]
#[UsesClass(Grai::class)]
#[UsesClass(Gdti::class)]
#[UsesClass(Gcn::class)]
#[UsesClass(Ginc::class)]
#[UsesClass(Gsin::class)]
#[UsesClass(Cpid::class)]
#[UsesClass(Gmn::class)]
final class DigitalLinkTest extends TestCase
{
    /**
     * The examples of GS1 Digital Link URI Syntax 1.7.0, section 5, with their element strings. Where the standard
     * lists the AIs in another order than path then query, the element string here follows the URI.
     *
     * @return iterable<string, array{non-empty-string, non-empty-string, non-empty-string}>
     */
    public static function standardExamples(): iterable
    {
        yield '5.1 GTIN' => ['https://id.gs1.org/01/09520123456788', '(01)09520123456788', 'https://id.gs1.org'];
        yield '5.1 custom path' => ['https://brand.example.com/some-extra/pathinfo/01/09520123456788', '(01)09520123456788', 'https://brand.example.com/some-extra/pathinfo'];
        yield '5.2 GTIN + CPV' => ['https://id.gs1.org/01/09520123456788/22/2A', '(01)09520123456788(22)2A', 'https://id.gs1.org'];
        yield '5.3 GTIN + batch' => ['https://id.gs1.org/01/09520123456788/10/ABC123', '(01)09520123456788(10)ABC123', 'https://id.gs1.org'];
        yield '5.4 GTIN + serial' => ['https://id.gs1.org/01/09520123456788/21/12345', '(01)09520123456788(21)12345', 'https://id.gs1.org'];
        yield '5.5 GTIN + batch + serial + expiry' => ['https://id.gs1.org/01/09520123456788/10/ABC1/21/12345?17=180426', '(01)09520123456788(10)ABC1(21)12345(17)180426', 'https://id.gs1.org'];
        yield '5.6 GTIN + net weight' => ['https://id.gs1.org/01/09520123456788?3103=000195', '(01)09520123456788(3103)000195', 'https://id.gs1.org'];
        yield '5.7 three attributes' => ['https://example.com/01/09520123456788?3103=000195&3922=0299&17=201225', '(01)09520123456788(3103)000195(3922)0299(17)201225', 'https://example.com'];
        yield '5.8 SSCC' => ['https://id.gs1.org/00/195201234567891232', '(00)195201234567891232', 'https://id.gs1.org'];
        yield '5.9 SSCC + content' => ['https://id.gs1.org/00/195201234567891232?02=09520123456788&37=25&10=ABC123', '(00)195201234567891232(02)09520123456788(37)25(10)ABC123', 'https://id.gs1.org'];
        yield '5.10 GLN' => ['https://id.gs1.org/414/9520123456788', '(414)9520123456788', 'https://id.gs1.org'];
        yield '5.10 GLN + extension' => ['https://id.gs1.org/414/9520123456788/254/32a%2Fb', '(414)9520123456788(254)32a/b', 'https://id.gs1.org'];
        yield '5.11 GIAI in the path' => ['https://example.com/8004/9520614141234567?01=09520123456788', '(8004)9520614141234567(01)09520123456788', 'https://example.com'];
        yield '5.11 GTIN in the path' => ['https://example.com/01/09520123456788?8004=9520614141234567', '(01)09520123456788(8004)9520614141234567', 'https://example.com'];
    }

    #[DataProvider('standardExamples')]
    public function testReadsTheStandardExamples(string $uri, string $elementString, string $domain): void
    {
        $link = DigitalLink::from($uri);

        self::assertSame($uri, (string) $link);
        self::assertSame($elementString, (string) $link->toElementString());
    }

    #[DataProvider('standardExamples')]
    public function testWritesTheStandardExamples(string $uri, string $elementString, string $domain): void
    {
        self::assertSame($uri, (string) DigitalLink::fromElementString(ElementString::from($elementString), $domain));
    }

    public function testTheStandardElementStringOfExample55GivesTheCanonicalUri(): void
    {
        $link = DigitalLink::fromElementString(ElementString::from('(01)09520123456788(17)180426(10)ABC1(21)12345'));

        self::assertSame('https://id.gs1.org/01/09520123456788/10/ABC1/21/12345?17=180426', (string) $link);
    }

    public function testDefaultsToTheCanonicalDomain(): void
    {
        self::assertSame('https://id.gs1.org/01/09520123456788', (string) DigitalLink::fromElementString(ElementString::from('(01)09520123456788')));
        self::assertSame('https://example.com/01/09520123456788', (string) DigitalLink::fromElementString(ElementString::from('(01)09520123456788'), 'https://example.com/'));
    }

    public function testKeepsNonGs1QueryParametersAsGiven(): void
    {
        $uri = 'https://example.com/01/09520123456788/10/ABC1?linkType=gs1:traceability&17=201225&context=x;flag&b=1=2';
        $link = DigitalLink::from($uri);

        self::assertSame($uri, (string) $link);
        self::assertSame('(01)09520123456788(10)ABC1(17)201225', (string) $link->toElementString());
        self::assertSame('https://id.gs1.org/01/09520123456788/10/ABC1?17=201225', (string) $link->toCanonical());
    }

    public function testCanonicalFormOfTheStandard(): void
    {
        self::assertSame(
            'https://id.gs1.org/01/09520123456788/22/2A',
            (string) DigitalLink::from('http://example.com/01/09520123456788/22/2A?linkType=gs1:traceability')->toCanonical(),
        );
        self::assertSame(
            'https://id.gs1.org/01/09520123456788?17=201225&3103=000195&3922=0299',
            (string) DigitalLink::from('https://id.gs1.org/01/09520123456788?3103=000195&3922=0299&17=201225')->toCanonical(),
        );
        self::assertSame(
            'https://id.gs1.org/414/9520123456788/254/32a%2Fb',
            (string) DigitalLink::from('https://example.com/414/9520123456788/254/32a%2fb/')->toCanonical(),
        );
    }

    public function testQueryValuesArePercentDecodedAndPlusIsASpace(): void
    {
        self::assertSame('A+B', DigitalLink::from('https://example.com/00/195201234567891232?10=A%2BB')->toElementString()->get('10'));

        $this->expectException(InvalidDigitalLinkException::class);
        $this->expectExceptionMessage('GS1 Digital Link data is not a valid element string: Element string AI data contains a character outside its GS1 character set.');

        DigitalLink::from('https://example.com/00/195201234567891232?10=A+B');
    }

    public function testAcceptsTheSemicolonDelimiterAndATrailingSlash(): void
    {
        $link = DigitalLink::from('https://example.com/01/09520123456788/10/ABC1/?17=201225;3103=000195');

        self::assertSame('(01)09520123456788(10)ABC1(17)201225(3103)000195', (string) $link->toElementString());
    }

    public function testAcceptsLegacyZeroSuppressedGtins(): void
    {
        $link = DigitalLink::from('https://example.com/01/9520123456788');

        self::assertSame('https://example.com/01/9520123456788', (string) $link);
        self::assertSame('(01)09520123456788', (string) $link->toElementString());
        self::assertTrue($link->equals(DigitalLink::from('https://example.com/01/09520123456788')));
        self::assertSame('(8004)9520614141234567(01)00000095010939', (string) DigitalLink::from('https://example.com/8004/9520614141234567?01=95010939')->toElementString());
        self::assertSame('(01)00614141123452', (string) DigitalLink::from('https://example.com/01/614141123452')->toElementString());
        self::assertSame('(00)195201234567891232(02)00000095010939(37)1', (string) DigitalLink::from('https://example.com/00/195201234567891232?02=95010939&37=1')->toElementString());
    }

    public function testEqualityFollowsTheDataNotTheSpelling(): void
    {
        $link = DigitalLink::from('https://example.com/01/09520123456788?3103=000195&17=201225&linkType=x');

        self::assertTrue($link->equals(DigitalLink::from('HTTPS://EXAMPLE.com/01/09520123456788?17=201225&linkType=x&3103=000195')));
        self::assertTrue($link->equals(DigitalLink::from('https://example.com/01/09520123456788/?17=201225;3103=000195&linkType=x')));
        self::assertTrue($link->equals(DigitalLink::from('https://example.com/01/0952012345678%38?17=201225&3103=000195&linkType=x')));
        self::assertFalse($link->equals(DigitalLink::from('http://example.com/01/09520123456788?3103=000195&17=201225&linkType=x')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.org/01/09520123456788?3103=000195&17=201225&linkType=x')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/a/01/09520123456788?3103=000195&17=201225&linkType=x')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/01/09520123456788?3103=000195&17=201225&linkType=y')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/01/09520123456788?3103=000195&17=201225')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/01/09520123456788?3103=000195&17=201225&linkType=x#f')));
        self::assertFalse($link->equals(DigitalLink::from('https://user@example.com/01/09520123456788?3103=000195&17=201225&linkType=x')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com:8443/01/09520123456788?3103=000195&17=201225&linkType=x')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/01/09520123456788/10/1?3103=000195&17=201225&linkType=x')));
        self::assertFalse($link->equals(Url::from('https://example.com/01/09520123456788?3103=000195&17=201225&linkType=x')));

        $giai = DigitalLink::from('https://example.com/8004/9520614141234567?01=09520123456788');
        self::assertFalse($giai->equals(DigitalLink::from('https://example.com/01/09520123456788?8004=9520614141234567')));
        self::assertTrue($giai->equals($giai));
    }

    public function testAuthorityAndFragmentTakePartInEquality(): void
    {
        $link = DigitalLink::from('https://User@Example.com/01/09520123456788#a');

        self::assertTrue($link->equals(DigitalLink::from('HTTPS://User@example.COM/01/09520123456788#a')));
        self::assertFalse($link->equals(DigitalLink::from('https://user@example.com/01/09520123456788#a')));
        self::assertFalse($link->equals(DigitalLink::from('https://User@fxample.com/01/09520123456788#a')));
        self::assertFalse(DigitalLink::from('https://a.example.com/01/09520123456788')->equals(DigitalLink::from('https://b.example.com/01/09520123456788')));
        self::assertTrue(DigitalLink::from('https://u%40ser@Example.com:8443/01/09520123456788')->equals(DigitalLink::from('https://u%40ser@example.com:8443/01/09520123456788')));
        self::assertFalse(DigitalLink::from('https://U%40ser@example.com/01/09520123456788')->equals(DigitalLink::from('https://u%40ser@example.com/01/09520123456788')));
        self::assertFalse($link->equals(DigitalLink::from('https://User@example.com/01/09520123456788#b')));
        self::assertFalse($link->equals(DigitalLink::from('https://User@example.com/01/09520123456788#')));
        self::assertFalse(DigitalLink::from('https://example.com/01/09520123456788#')->equals(DigitalLink::from('https://example.com/01/09520123456788')));
    }

    public function testPercentEncodedAiKeysAreDecodedBeforeTheLookup(): void
    {
        $query = DigitalLink::from('https://example.com/01/09520123456788?%31%37=250101');
        self::assertSame('(01)09520123456788(17)250101', (string) $query->toElementString());
        self::assertTrue($query->equals(DigitalLink::from('https://example.com/01/09520123456788?17=250101')));

        $path = DigitalLink::from('https://example.com/%30%31/09520123456788/%31%30/ABC');
        self::assertSame('(01)09520123456788(10)ABC', (string) $path->toElementString());
        self::assertTrue($path->equals(DigitalLink::from('https://example.com/01/09520123456788/10/ABC')));
        self::assertSame('(01)09520123456788', (string) DigitalLink::from('https://example.com/gt%69n/09520123456788')->toElementString());
    }

    public function testAKeyQualifierInTheQueryIsReadAsTheQualifier(): void
    {
        $link = DigitalLink::from('https://example.com/01/09520123456788/21/S?10=A&17=250101');

        self::assertSame('(01)09520123456788(10)A(21)S(17)250101', (string) $link->toElementString());
        self::assertTrue($link->equals(DigitalLink::from('https://example.com/01/09520123456788/10/A/21/S?17=250101')));
        self::assertSame('https://id.gs1.org/01/09520123456788/10/A/21/S?17=250101', (string) $link->toCanonical());
        self::assertSame('(01)09520123456788(10)ABC', (string) DigitalLink::from('https://example.com/01/09520123456788?10=ABC')->toElementString());
        self::assertTrue(DigitalLink::from('https://example.com/01/09520123456788/10/L')->equals(DigitalLink::from('https://example.com/01/09520123456788?10=L')));
        self::assertTrue(DigitalLink::from('https://example.com/01/09520123456788?10=L')->equals(DigitalLink::from('https://example.com/01/09520123456788/10/L')));
        self::assertSame('https://id.gs1.org/01/09520123456788/22/V/10/A/21/S', (string) DigitalLink::from('https://example.com/01/09520123456788?21=S&10=A&22=V')->toCanonical());
    }

    public function testEqualityNormalisesPercentEscapesButKeepsPortAndParameterOrder(): void
    {
        $link = DigitalLink::from('https://example.com/a%2fb/01/09520123456788?x=%2f&y=1#f%2f');

        self::assertTrue($link->equals(DigitalLink::from('https://example.com/a%2Fb/01/09520123456788?x=%2F&y=1#f%2F')));
        self::assertTrue(DigitalLink::from('https://example.com/%7Estem/01/09520123456788')->equals(DigitalLink::from('https://example.com/~stem/01/09520123456788')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/a%2Fb/01/09520123456788?y=1&x=%2F#f%2F')));
        self::assertFalse(DigitalLink::from('https://example.com:443/01/09520123456788')->equals(DigitalLink::from('https://example.com/01/09520123456788')));
        self::assertFalse(DigitalLink::from('https://example.com/01/09520123456788?x=%2F')->equals(DigitalLink::from('https://example.com/01/09520123456788?x=%3A')));
        self::assertFalse(DigitalLink::from('https://example.com/01/09520123456788?x=%2F')->equals(DigitalLink::from('https://example.com/01/09520123456788?x=%252F')));
    }

    public function testAcceptsAnIpv6Host(): void
    {
        $link = DigitalLink::from('https://[2001:DB8::1]:8443/01/09520123456788');

        self::assertSame('(01)09520123456788', (string) $link->toElementString());
        self::assertTrue($link->equals(DigitalLink::from('https://[2001:db8::1]:8443/01/09520123456788')));
    }

    public function testAStemPathThatLooksLikeGs1DataStaysTheStem(): void
    {
        $link = DigitalLink::from('https://example.com/414/9520123456788/01/09520123456788');

        self::assertSame('(01)09520123456788', (string) $link->toElementString());
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/01/09520123456788')));
        self::assertSame('(01)09520123456788(10)A+B', (string) DigitalLink::from('https://example.com/01/09520123456788/10/A+B')->toElementString());
    }

    public function testAQualifierOfAnotherSequenceStaysAnAttributeBothWays(): void
    {
        $link = DigitalLink::from('https://example.com/01/09520123456788/235/X1?10=AB');

        self::assertSame('(01)09520123456788(235)X1(10)AB', (string) $link->toElementString());
        self::assertSame((string) $link, (string) DigitalLink::fromElementString($link->toElementString(), 'https://example.com'));
        self::assertSame('https://id.gs1.org/01/09520123456788/235/X1?10=AB', (string) DigitalLink::fromElementString(ElementString::from('(01)09520123456788(10)AB(235)X1')));
    }

    public function testARepeatedAttributeTakesItsLastValue(): void
    {
        $link = DigitalLink::from('https://example.com/01/09520123456788?17=201225&3103=000195&17=211225');

        self::assertSame('(01)09520123456788(3103)000195(17)211225', (string) $link->toElementString());
        self::assertTrue($link->equals(DigitalLink::from('https://example.com/01/09520123456788?17=211225&3103=000195')));
        self::assertFalse($link->equals(DigitalLink::from('https://example.com/01/09520123456788?17=201225&3103=000195')));
    }

    public function testAcceptsConvenienceAlphas(): void
    {
        $link = DigitalLink::from('https://example.com/gtin/9520123456788/lot/ABC/ser/1');

        self::assertSame('https://example.com/gtin/9520123456788/lot/ABC/ser/1', (string) $link);
        self::assertSame('(01)09520123456788(10)ABC(21)1', (string) $link->toElementString());
        self::assertTrue($link->equals(DigitalLink::from('https://example.com/01/09520123456788/10/ABC/21/1')));
        self::assertSame('https://id.gs1.org/01/09520123456788/10/ABC/21/1', (string) $link->toCanonical());
        self::assertSame('(414)9520123456788(254)X', (string) DigitalLink::from('https://example.com/gln/9520123456788/glnx/X')->toElementString());
        self::assertSame('(01)09520123456788', (string) DigitalLink::from('https://example.com/01/09520123456788?lot=A')->toElementString());
        self::assertSame('(8010)95211415PQ7(8011)0', (string) DigitalLink::from('https://example.com/cpid/95211415PQ7/cpsn/0')->toElementString());
    }

    public function testAcceptsTheLongestUri(): void
    {
        $prefix = 'https://example.com/01/09520123456788?a=';
        $uri = $prefix . \str_repeat('b', DigitalLinkSyntax::MAX_LENGTH - \strlen($prefix));

        self::assertSame($uri, (string) DigitalLink::from($uri));
    }

    public function testExposesTheUrl(): void
    {
        $link = DigitalLink::from('https://example.com/some/path/01/09520123456788#top');

        self::assertSame('example.com', (string) $link->url()->host());
        self::assertSame('/some/path/01/09520123456788', $link->url()->path());
    }

    /**
     * @return iterable<string, array{non-empty-string, class-string<IdentificationKey>, non-empty-string}>
     */
    public static function primaryKeys(): iterable
    {
        yield 'GTIN' => ['https://example.com/01/09520123456788/21/1', Gtin::class, '09520123456788'];
        yield 'GTIN legacy' => ['https://example.com/01/9520123456788', Gtin::class, '09520123456788'];
        yield 'ITIP' => ['https://example.com/8006/095201234567880102', Gtin::class, '09520123456788'];
        yield 'SSCC' => ['https://example.com/00/195201234567891232', Sscc::class, '195201234567891232'];
        yield 'GLN' => ['https://example.com/414/9520123456788', Gln::class, '9520123456788'];
        yield 'party GLN' => ['https://example.com/417/9520123456788', Gln::class, '9520123456788'];
        yield 'pay-to GLN' => ['https://example.com/415/9520123456788/8020/REF1', Gln::class, '9520123456788'];
        yield 'GIAI' => ['https://example.com/8004/9520614141234567', Giai::class, '9520614141234567'];
        yield 'GSRN recipient' => ['https://example.com/8018/952011410000000012', Gsrn::class, '952011410000000012'];
        yield 'GSRN provider' => ['https://example.com/8017/952011410000000012/8019/12', Gsrn::class, '952011410000000012'];
        yield 'GRAI' => ['https://example.com/8003/09521141123454A1', Grai::class, '09521141123454A1'];
        yield 'GDTI' => ['https://example.com/253/9521141123454X', Gdti::class, '9521141123454X'];
        yield 'GCN' => ['https://example.com/255/952114167890904711', Gcn::class, '952114167890904711'];
        yield 'GINC' => ['https://example.com/401/9521141xyz47%2F11', Ginc::class, '9521141xyz47/11'];
        yield 'GSIN' => ['https://example.com/402/95211411234567892', Gsin::class, '95211411234567892'];
        yield 'CPID' => ['https://example.com/8010/95211415PQ7%2FZ43/8011/12345', Cpid::class, '95211415PQ7/Z43'];
        yield 'GMN' => ['https://example.com/8013/1987654Ad4X4bL5ttr2310c2K', Gmn::class, '1987654Ad4X4bL5ttr2310c2K'];
    }

    /**
     * @param class-string<IdentificationKey> $class
     */
    #[DataProvider('primaryKeys')]
    public function testPrimaryKey(string $uri, string $class, string $value): void
    {
        $key = DigitalLink::from($uri)->primaryKey();

        self::assertInstanceOf($class, $key);
        self::assertInstanceOf(AbstractStringValueObject::class, $key);
        self::assertSame($value, (string) $key);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidUris(): iterable
    {
        yield 'empty' => ['', 'GS1 Digital Link is not an http or https URL.'];
        yield 'too long' => ['https://example.com/01/09520123456788?a=' . \str_repeat('b', 14200), 'GS1 Digital Link is longer than 14178 characters.'];
        yield 'space' => ['https://example.com/01/09520123456788?a=b c', 'GS1 Digital Link contains a character that a URI does not allow.'];
        yield 'non-ASCII' => ["https://example.com/01/09520123456788?a=\u{e9}", 'GS1 Digital Link contains a character that a URI does not allow.'];
        yield 'other scheme' => ['ftp://example.com/01/09520123456788', 'GS1 Digital Link is not an http or https URL.'];
        yield 'no host' => ['https:/01/09520123456788', 'GS1 Digital Link is not an http or https URL.'];
        yield 'no path' => ['https://example.com?01=09520123456788', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'no key' => ['https://example.com/a/b', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'qualifier without key' => ['https://example.com/10/ABC', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'unknown AI before the key' => ['https://example.com/01/09520123456788/foo/bar', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'eh inside a segment' => ['https://example.com/a/xeh', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'eh before other characters' => ['https://example.com/a/eh-', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'unknown alpha' => ['https://example.com/cps/09520123456788', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'bare AI key' => ['https://example.com/01/09520123456788?17', 'GS1 Digital Link query has an AI without a value.'];
        yield 'bare numeric key' => ['https://example.com/01/09520123456788?236', 'GS1 Digital Link query has an all-numeric key that is no AI.'];
        yield 'compressed' => ['https://id.gs1.org/ARHKVAdpQgTSoTF0', 'GS1 Digital Link is compressed; GS1 Digital Link URI Syntax 1.7.0 defines no compression.'];
        yield 'compressed EPC' => ['https://id.gs1.org/eh0123abcd', 'GS1 Digital Link is compressed; GS1 Digital Link URI Syntax 1.7.0 defines no compression.'];
        yield 'empty value' => ['https://example.com/01//10/A', 'GS1 Digital Link path has an empty value.'];
        yield 'empty qualifier' => ['https://example.com/01/09520123456788/10/', 'GS1 Digital Link has no GS1 primary key in its path.'];
        yield 'qualifier order' => ['https://example.com/01/09520123456788/21/12345XYZ/10/ABC123', 'GS1 Digital Link path has key qualifiers that its primary key does not take in this order.'];
        yield 'foreign qualifier' => ['https://example.com/00/195201234567891232/10/ABC', 'GS1 Digital Link path has key qualifiers that its primary key does not take in this order.'];
        yield 'repeated qualifier' => ['https://example.com/01/09520123456788/10/A/10/A', 'GS1 Digital Link path has key qualifiers that its primary key does not take in this order.'];
        yield 'two "@" in the authority' => ['https://u@ser@example.com/01/09520123456788', 'GS1 Digital Link authority has more than one "@".'];
        yield 'broken escape in a value' => ['https://example.com/01/09520123456788/10/ABC%2', 'GS1 Digital Link contains a "%" that is not followed by two hex digits.'];
        yield 'escape with a non-hex digit' => ['https://example.com/01/09520123456788?x=%G1', 'GS1 Digital Link contains a "%" that is not followed by two hex digits.'];
        yield 'escaped numeric key that is no AI' => ['https://example.com/01/09520123456788?%39%39%39%39=1', 'GS1 Digital Link query has an all-numeric key that is no AI.'];
        yield 'not an attribute' => ['https://example.com/00/195201234567891232?21=1', 'GS1 Digital Link query has an AI that is not a GS1 Digital Link data attribute.'];
        yield 'unknown numeric key' => ['https://example.com/01/09520123456788?236=12098', 'GS1 Digital Link query has an all-numeric key that is no AI.'];
        yield 'key repeated in query' => ['https://example.com/01/09520123456788?01=09520123456788', 'GS1 Digital Link has an AI in the path and in the query.'];
        yield 'qualifier repeated in query' => ['https://example.com/01/09520123456788/10/A?10=B', 'GS1 Digital Link has an AI in the path and in the query.'];
        yield 'empty attribute' => ['https://example.com/01/09520123456788?17=', 'GS1 Digital Link query has an AI without a value.'];
        yield 'NUL in path' => ['https://example.com/01/0952012345678%008', 'GS1 Digital Link data contains a NUL character.'];
        yield 'NUL in query' => ['https://example.com/01/09520123456788?17=20122%00', 'GS1 Digital Link data contains a NUL character.'];
        yield 'check digit' => ['https://example.com/01/09520123456789', 'GS1 Digital Link data is not a valid element string: Element string AI data fails a GS1 check of its AI.'];
        yield 'invalid pair' => ['https://example.com/01/09520123456788?37=1', 'GS1 Digital Link data is not a valid element string: Element string has two AIs that must not appear together.'];
    }

    #[DataProvider('invalidUris')]
    public function testRefuses(string $uri, string $message): void
    {
        try {
            DigitalLink::from($uri);
            self::fail('Expected InvalidDigitalLinkException.');
        } catch (InvalidDigitalLinkException $exception) {
            self::assertSame($message, $exception->getMessage());
        }
    }

    public function testMessagesNeverEchoTheInput(): void
    {
        try {
            DigitalLink::from('https://secret.example.com/01/09520123456789?secret=1');
            self::fail('Expected InvalidDigitalLinkException.');
        } catch (InvalidDigitalLinkException $exception) {
            self::assertStringNotContainsString('secret', $exception->getMessage());
            self::assertStringNotContainsString('0952012345678', $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{non-empty-string, string, string}>
     */
    public static function unconvertibleElementStrings(): iterable
    {
        yield 'no primary key' => ['(10)ABC', 'https://id.gs1.org', 'GS1 Digital Link needs a primary key AI in the element string.'];
        yield 'not an attribute' => ['(414)9520123456788(7040)1A2B(91)X(8019)1', 'https://id.gs1.org', 'GS1 Digital Link cannot carry an AI of the element string: it is neither a key qualifier of the primary key nor a data attribute.'];
        yield 'qualifier of another key' => ['(01)09520123456788(7040)1A2B', 'https://id.gs1.org', 'GS1 Digital Link cannot carry an AI of the element string: it is neither a key qualifier of the primary key nor a data attribute.'];
        yield 'domain with query' => ['(01)09520123456788', 'https://example.com/?a=1', 'GS1 Digital Link domain must be an http or https URL without query or fragment.'];
        yield 'domain with fragment' => ['(01)09520123456788', 'https://example.com#a', 'GS1 Digital Link domain must be an http or https URL without query or fragment.'];
        yield 'domain not a URL' => ['(01)09520123456788', 'example.com', 'GS1 Digital Link domain must be an http or https URL without query or fragment.'];
    }

    #[DataProvider('unconvertibleElementStrings')]
    public function testFromElementStringRefuses(string $elementString, string $domain, string $message): void
    {
        $this->expectException(InvalidDigitalLinkException::class);
        $this->expectExceptionMessage($message);

        DigitalLink::fromElementString(ElementString::from($elementString), $domain);
    }

    public function testFromElementStringPicksTheFirstPrimaryKeyAndTheLongestQualifierSequence(): void
    {
        self::assertSame(
            'https://id.gs1.org/01/09520123456788/22/2A/10/B/21/C?17=201225',
            (string) DigitalLink::fromElementString(ElementString::from('(17)201225(21)C(10)B(01)09520123456788(22)2A')),
        );
        self::assertSame(
            'https://id.gs1.org/414/9520123456788/254/A%28%25',
            (string) DigitalLink::fromElementString(ElementString::from('(414)9520123456788(254)A\(%')),
        );
        self::assertSame(
            'https://id.gs1.org/01/09520123456788/235/X1',
            (string) DigitalLink::fromElementString(ElementString::from('(01)09520123456788(235)X1')),
        );
        self::assertSame(
            'https://id.gs1.org/01/09520123456788?17=201225',
            (string) DigitalLink::fromElementString(ElementString::from('(01)09520123456788(17)201225(17)201225')),
        );
    }

    public function testRoundTripsAWideQueryInLinearTime(): void
    {
        $query = \implode('&', \array_fill(0, 2000, 'x=1'));
        $link = DigitalLink::from('https://example.com/01/09520123456788?' . $query . '&17=201225');

        self::assertSame('(01)09520123456788(17)201225', (string) $link->toElementString());
    }

    public function testHelpersRefuseImpossibleInput(): void
    {
        $this->expectException(LogicException::class);

        DigitalLinkSyntax::keyFor(new AiElement(AiTable::spec('10') ?? throw new LogicException(), 'ABC'));
    }
}
