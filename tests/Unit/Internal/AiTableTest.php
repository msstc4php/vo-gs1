<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Gs1\Internal\AiCharset;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiTable::class)]
#[CoversClass(AiSpec::class)]
#[CoversClass(AiComponent::class)]
final class AiTableTest extends TestCase
{
    public function testGtinIsFourteenDigitsWithCheckDigitAndNoFnc1(): void
    {
        $spec = AiTable::spec('01');

        self::assertEquals(
            new AiSpec(
                '01',
                'GTIN',
                [new AiComponent(AiCharset::Numeric, 14, 14, false, ['csum', 'gcppos2'])],
                false,
                [],
                ['255', '37'],
                true,
                [['22', '10', '21'], ['235']],
            ),
            $spec,
        );
    }

    public function testBatchIsUpToTwentyCset82CharactersWithFnc1(): void
    {
        $spec = AiTable::spec('10');

        self::assertEquals(
            new AiSpec(
                '10',
                'BATCH/LOT',
                [new AiComponent(AiCharset::Cset82, 1, 20, false, [])],
                true,
                [[['01'], ['02'], ['03'], ['8006'], ['8026']]],
                [],
                true,
                null,
            ),
            $spec,
        );
    }

    public function testRequirementGroupsAndJoinedAlternativesAreKept(): void
    {
        // 37: "req=00 req=02,8026" (two groups); 8030: "01+21" needs both.
        self::assertSame([[['00']], [['02'], ['8026']]], AiTable::spec('37')?->requires);
        self::assertContains(['01', '21'], AiTable::spec('8030')?->requires[0] ?? []);
    }

    public function testOptionalComponentsAndSeveralLinters(): void
    {
        $spec = AiTable::spec('423');
        self::assertNotNull($spec);
        $components = $spec->components;

        self::assertCount(5, $components);
        self::assertFalse($components[0]->optional);
        self::assertTrue($components[4]->optional);
        self::assertSame(['iso3166'], $components[4]->linters);
        self::assertSame(['csum', 'gcppos1'], AiTable::spec('8003')?->components[1]->linters);
        self::assertSame(AiCharset::Base64Url, AiTable::spec('8030')?->components[0]->charset);
        self::assertSame(AiCharset::Cset39, AiTable::spec('8010')?->components[0]->charset);
    }

    public function testDigitalLinkPrimaryKeyWithoutQualifiersIsAnEmptyList(): void
    {
        self::assertSame([], AiTable::spec('00')?->digitalLinkPrimaryKey);
        self::assertNull(AiTable::spec('21')?->digitalLinkPrimaryKey);
        self::assertFalse(AiTable::spec('21')?->digitalLinkAttribute);
    }

    public function testEntryWithoutTitle(): void
    {
        self::assertNull(AiTable::spec('8110')?->title);
    }

    /**
     * @return iterable<string, array{non-empty-string, non-empty-string|null}>
     */
    public static function rangeMembers(): iterable
    {
        yield 'first of 3100-3105' => ['3100', 'NET WEIGHT (kg)'];
        yield 'last of 3100-3105' => ['3105', 'NET WEIGHT (kg)'];
        yield 'first of 91-99' => ['91', 'INTERNAL'];
        yield 'last of 91-99' => ['99', 'INTERNAL'];
        yield 'last of 3900-3909' => ['3909', 'AMOUNT'];
    }

    #[DataProvider('rangeMembers')]
    public function testRangesAreExpandedPerAi(string $ai, ?string $title): void
    {
        self::assertSame($title, AiTable::spec($ai)?->title);
        self::assertSame($ai, AiTable::spec($ai)?->ai);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownAis(): iterable
    {
        yield 'empty' => [''];
        yield 'one digit' => ['0'];
        yield 'after a range' => ['3106'];
        yield 'unassigned' => ['14'];
        yield 'padded' => ['010'];
        yield 'integer key without leading zero' => ['1'];
        yield 'whitespace' => [' 10'];
        yield 'exponent' => ['1e1'];
    }

    #[DataProvider('unknownAis')]
    public function testUnknownAis(string $ai): void
    {
        self::assertNull(AiTable::spec($ai));
    }

    /**
     * @return iterable<string, array{string, string|null}>
     */
    public static function prefixes(): iterable
    {
        yield 'two digits' => ['0109506000134352', '01'];
        yield 'three digits' => ['4149521234500018', '414'];
        yield 'four digits' => ['3103000150', '3103'];
        yield 'exactly the AI' => ['10', '10'];
        yield 'data shorter than an AI' => ['3', null];
        yield 'empty' => ['', null];
        yield 'no AI' => ['1499', null];
        yield 'letters' => ['AB12', null];
    }

    #[DataProvider('prefixes')]
    public function testMatchPrefix(string $data, ?string $ai): void
    {
        self::assertSame($ai, AiTable::matchPrefix($data)?->ai);
    }

    public function testAisAreStringsInDictionaryOrderAndPrefixFree(): void
    {
        $ais = AiTable::ais();

        self::assertSame('00', $ais[0]);
        self::assertContains('10', $ais);
        self::assertSame('99', $ais[\count($ais) - 1]);
        foreach ($ais as $ai) {
            self::assertSame($ai, AiTable::matchPrefix($ai . '123456')?->ai);
        }
    }
}
