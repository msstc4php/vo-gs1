<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\ElementString;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\UnmetRequirement;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\UnresolvableDateException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Key\Sscc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ElementString::class)]
#[CoversClass(UnmetRequirement::class)]
#[CoversClass(ElementStringSyntax::class)]
#[CoversClass(AiElement::class)]
#[CoversClass(Gs1Dates::class)]
#[UsesClass(AiLinters::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(GmnCheckCharacters::class)]
#[UsesClass(KeyRules::class)]
#[UsesClass(Gtin::class)]
#[UsesClass(Sscc::class)]
final class ElementStringTest extends TestCase
{
    private const string GTIN = '09506000134352';

    public function testScannerStringWithSymbologyIdentifierAndGroupSeparatorsEqualsItsBracketedForm(): void
    {
        $scanned = ElementString::from("]d201095060001343521721122510ABC\x1D21XYZ");
        $bracketed = ElementString::from('(01)09506000134352(17)211225(10)ABC(21)XYZ');

        self::assertTrue($scanned->equals($bracketed));
        self::assertSame('(01)09506000134352(17)211225(10)ABC(21)XYZ', (string) $scanned);
        self::assertSame(['01', '17', '10', '21'], $scanned->ais());
        self::assertSame("01095060001343521721122510ABC\x1D21XYZ", $scanned->toBarcodeData());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notations(): iterable
    {
        yield 'bracketed' => ['(01)09506000134352(10)ABC123(11)250504'];
        yield 'GS separators' => ["010950600013435210ABC123\x1D11250504"];
        yield 'caret separators' => ['^010950600013435210ABC123^11250504'];
        yield 'leading GS' => ["\x1D010950600013435210ABC123\x1D11250504"];
        yield 'GS1-128' => ["]C1010950600013435210ABC123\x1D11250504"];
        yield 'GS1 DataBar' => ["]e0010950600013435210ABC123\x1D11250504"];
        yield 'GS1 DataMatrix' => ["]d2010950600013435210ABC123\x1D11250504"];
        yield 'GS1 QR Code' => ["]Q3010950600013435210ABC123\x1D11250504"];
        yield 'GS1 DotCode' => ["]J1010950600013435210ABC123\x1D11250504"];
        yield 'separator after a predefined-length AI' => ["0109506000134352\x1D10ABC123\x1D11250504"];
        yield 'trailing separator' => ["010950600013435210ABC123\x1D11250504\x1D"];
    }

    #[DataProvider('notations')]
    public function testNotationsDifferingOnlyInSyntaxAreEqual(string $input): void
    {
        $element = ElementString::from($input);

        self::assertSame('(01)09506000134352(10)ABC123(11)250504', $element->value);
        self::assertTrue($element->equals(ElementString::from('(01)09506000134352(10)ABC123(11)250504')));
        self::assertSame("010950600013435210ABC123\x1D11250504", $element->toBarcodeData());
    }

    public function testCpidSerialMayBeTheSingleDigitZero(): void
    {
        // GenSpecs 26.0, 3.9.11: no leading "0" "unless the entire serial number consists of the single digit "0"".
        self::assertSame('0', ElementString::from('(8010)9521141ABC(8011)0')->get('8011'));

        $this->expectException(InvalidElementStringException::class);
        ElementString::from('(8010)9521141ABC(8011)01');
    }

    public function testParenthesisInsideSerialSurvivesTheRoundTrip(): void
    {
        $element = ElementString::from('(01)09506000134352(21)AB\(C(10)X');

        self::assertSame('AB(C', $element->serial());
        self::assertSame('(01)09506000134352(21)AB\(C(10)X', (string) $element);
        self::assertSame('0109506000134352' . "21AB(C\x1D10X", $element->toBarcodeData());
        self::assertTrue(ElementString::from($element->toBarcodeData())->equals($element));
        self::assertTrue(ElementString::from((string) $element)->equals($element));
    }

    public function testAiOrderIsKeptSoDifferentOrderIsADifferentValue(): void
    {
        $first = ElementString::from('(01)09506000134352(10)ABC(21)XYZ');
        $second = ElementString::from('(01)09506000134352(21)XYZ(10)ABC');

        self::assertSame(['01', '10', '21'], $first->ais());
        self::assertSame(['01', '21', '10'], $second->ais());
        self::assertFalse($first->equals($second));
    }

    public function testAccessors(): void
    {
        $element = ElementString::from('(01)09506000134352(17)261231(10)LOT1(21)SER1(11)250101');

        self::assertSame('LOT1', $element->batch());
        self::assertSame('SER1', $element->serial());
        self::assertSame('261231', $element->get('17'));
        self::assertTrue($element->has('17'));
        self::assertFalse($element->has('15'));
        self::assertNull($element->get('15'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notAnAi(): iterable
    {
        yield 'no AI' => ['1'];
        yield 'unassigned' => ['23'];
        yield 'pattern' => ['31nn'];
        yield 'bracketed' => ['(17)'];
    }

    #[DataProvider('notAnAi')]
    public function testGetRefusesAnArgumentThatIsNoAi(string $ai): void
    {
        $element = ElementString::from('(01)09506000134352(17)261231');

        try {
            $element->has($ai);
            self::fail('has() must refuse an argument that is no AI.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame('Element string accessors take an AI of the GS1 table.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $element->get($ai);
        $gtin = $element->gtin();
        self::assertNotNull($gtin);
        self::assertTrue(new Gtin(self::GTIN)->equals($gtin));
        self::assertSame(self::GTIN, (string) $gtin);
        self::assertNull($element->sscc());

        $sscc = ElementString::from('(00)095212340005678903');
        self::assertSame('095212340005678903', (string) $sscc->sscc());
        self::assertNull($sscc->gtin());
        self::assertNull($sscc->batch());
        self::assertNull($sscc->serial());
        self::assertNull($sscc->expiryDate());
        self::assertNull($sscc->productionDate());
    }

    public function testDatesAreMidnightUtc(): void
    {
        $today = new DateTimeImmutable('2026-11-19', new DateTimeZone('UTC'));
        $element = ElementString::from('(01)09506000134352(11)250504(17)271231');

        $expiry = $element->expiryDate($today);
        self::assertNotNull($expiry);
        self::assertSame('2027-12-31T00:00:00+00:00', $expiry->format(DATE_ATOM));
        self::assertSame('UTC', $expiry->getTimezone()->getName());
        self::assertSame('2025-05-04T00:00:00+00:00', $element->productionDate($today)?->format(DATE_ATOM));
    }

    public function testDefaultTodayIsTheCurrentUtcYear(): void
    {
        $nextYear = (int) \gmdate('Y') + 1;
        $element = ElementString::from(\sprintf('(01)09506000134352(17)%02d1231', $nextYear % 100));

        self::assertSame($nextYear . '-12-31', $element->expiryDate()?->format('Y-m-d'));
    }

    public function testTodayInAnotherZoneIsReadInUtc(): void
    {
        $element = ElementString::from('(01)09506000134352(17)770101');
        // 2027-01-01 01:00 in UTC+3 is still 2026 in UTC: 77 - 26 = 51 is the previous century.
        $today = new DateTimeImmutable('2027-01-01 01:00', new DateTimeZone('+03:00'));

        self::assertSame('1977-01-01', $element->expiryDate($today)?->format('Y-m-d'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dayZero(): iterable
    {
        yield 'GenSpecs 130200' => ['130200', '2013-02-28'];
        yield 'GenSpecs 160200' => ['160200', '2016-02-29'];
        yield 'thirty-day month' => ['250400', '2025-04-30'];
        yield 'December' => ['251200', '2025-12-31'];
    }

    #[DataProvider('dayZero')]
    public function testDayZeroIsTheLastDayOfTheMonth(string $yymmdd, string $expected): void
    {
        $today = new DateTimeImmutable('2026-11-19', new DateTimeZone('UTC'));

        self::assertSame($expected, ElementString::from('(01)09506000134352(17)' . $yymmdd)->expiryDate($today)?->format('Y-m-d'));
    }

    public function testTwentyNinthOfFebruary(): void
    {
        $today = new DateTimeImmutable('2026-11-19', new DateTimeZone('UTC'));

        self::assertSame('2024-02-29', ElementString::from('(01)09506000134352(17)240229')->expiryDate($today)?->format('Y-m-d'));
        self::assertNull(ElementString::tryFrom('(01)09506000134352(17)230229'));
        self::assertNull(ElementString::tryFrom('(01)09506000134352(17)250230'));
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function centuryWindow(): iterable
    {
        // GenSpecs 7.12: YY - current year in 51..99 => previous century; -50..-99 => next century.
        yield '+50 stays in the current century' => ['2026-11-19', '761231', '2076-12-31'];
        yield '+51 is the previous century' => ['2026-11-19', '770101', '1977-01-01'];
        yield '-49 stays in the current century' => ['2060-06-30', '110101', '2011-01-01'];
        yield '-50 is the next century' => ['2060-06-30', '100101', '2110-01-01'];
        yield 'same year' => ['2026-11-19', '260101', '2026-01-01'];
    }

    #[DataProvider('centuryWindow')]
    public function testCenturyWindowAtBothEdges(string $today, string $yymmdd, string $expected): void
    {
        $element = ElementString::from('(01)09506000134352(11)' . $yymmdd);

        self::assertSame($expected, $element->productionDate(new DateTimeImmutable($today, new DateTimeZone('UTC')))?->format('Y-m-d'));
    }

    public function testDateWithoutCalendarDayInTheChosenCenturyIsRefusedByTheAccessor(): void
    {
        // 000229 is 2000-02-29 now, but 2100-02-29 (no such day) for a "today" in 2060.
        $element = ElementString::from('(01)09506000134352(17)000229');

        $this->expectException(UnresolvableDateException::class);
        $this->expectExceptionMessage('Element string date is not a calendar date in the century chosen for the given day.');
        $element->expiryDate(new DateTimeImmutable('2060-01-01', new DateTimeZone('UTC')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function acceptedStrings(): iterable
    {
        yield 'company-internal 91 to 99' => ['(01)09506000134352(91)A(92)B(93)C(94)D(95)E(96)F(97)G(98)H(99)I'];
        yield 'internal 90' => ['(90)FREE-TEXT'];
        yield 'AI 91 at its maximum length' => ['(01)09506000134352(91)' . \str_repeat('x', 90)];
        yield 'SSCC with count of trade items' => ['(00)095212340005678903(02)04006381333931(37)12'];
        yield 'country of origin' => ['(01)09506000134352(422)643'];
        yield 'GIAI' => ['(8004)09521234500XY09'];
        yield 'pack date and best before' => ['(01)09506000134352(13)250100(15)251231'];
        yield 'repeated AI with the same value' => ['(01)09506000134352(10)A(01)09506000134352'];
        yield 'price with ISO 4217 currency' => ['(01)09506000134352(3102)000100(3932)643100'];
        yield 'GDTI with optional serial' => ['(253)9521234500001ABC'];
        yield 'GDTI without optional serial' => ['(253)9521234500001'];
        yield 'GMN' => ['(8013)1987654Ad4X4bL5ttr2310c2K'];
        yield 'production time with optional minutes' => ['(01)09506000134352(8008)25010112'];
        yield 'production time with minutes and seconds' => ['(01)09506000134352(8008)250101123059'];
        yield '29 February 2000 does not depend on the clock' => ['(01)09506000134352(17)000229'];
        yield 'batch without its GTIN: req is reported, not refused' => ['(10)ABC'];
        yield 'digital signature base64url with padding' => ['(00)095212340005678903(8030)AbC-_9=='];
    }

    #[DataProvider('acceptedStrings')]
    public function testAccepted(string $input): void
    {
        self::assertSame($input, (string) ElementString::from($input));
    }

    public function testUnmetRequirementsAreReportedNotRefused(): void
    {
        self::assertSame([], ElementString::from('(01)09506000134352(10)ABC(17)261231')->unmetRequirements());

        $batch = ElementString::from('(10)ABC')->unmetRequirements();
        self::assertCount(1, $batch);
        self::assertSame('10', $batch[0]->ai);
        self::assertSame([['01'], ['02'], ['03'], ['8006'], ['8026']], $batch[0]->alternatives);
        self::assertSame('(01) or (02) or (03) or (8006) or (8026)', $batch[0]->describe());

        $signature = ElementString::from('(01)09506000134352(8030)AbC')->unmetRequirements();
        self::assertCount(1, $signature);
        self::assertSame('(00) or (01)+(21) or (253) or (255) or (8003) or (8004) or (8006)+(21) or (8010)+(8011) or (8017) or (8018)', $signature[0]->describe());
        self::assertSame([], ElementString::from('(01)09506000134352(21)S(8030)AbC')->unmetRequirements());

        // AI (37) has two req attributes: (00) is met, (02) or (8026) is not.
        $count = ElementString::from('(00)095212340005678903(37)12')->unmetRequirements();
        self::assertCount(1, $count);
        self::assertSame('37', $count[0]->ai);
        self::assertSame('(02) or (8026)', $count[0]->describe());

        // Patterns: (3932) needs one of (30), (31nn), (32nn), (35nn), (36nn); a repeated AI is reported once.
        self::assertSame([], ElementString::from('(01)09506000134352(3102)000100(3932)643100')->unmetRequirements());
        self::assertCount(1, ElementString::from('(3932)643100(3932)643100')->unmetRequirements());
        self::assertSame('3932', ElementString::from('(01)09506000134352(3932)643100')->unmetRequirements()[0]->ai);
    }

    public function testRepeatedAiIsListedOnce(): void
    {
        self::assertSame(['01', '10'], ElementString::from('(01)09506000134352(10)A(01)09506000134352')->ais());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function refusedStrings(): iterable
    {
        yield 'empty' => ['', 'Element string is empty.'];
        yield 'symbology identifier only' => [']d2', 'Element string is empty.'];
        yield 'separator only' => ["\x1D", 'Element string is empty.'];
        yield 'unknown AI bracketed' => ['(01)09506000134352(23)1', 'Element string contains an unknown AI.'];
        yield 'unknown AI unbracketed' => ['0109506000134352231', 'Element string contains an unknown AI.'];
        yield 'unknown symbology identifier' => [']d10109506000134352', 'Element string has an unsupported symbology identifier.'];
        yield 'unclosed AI' => ['(01', 'Element string has an unclosed AI bracket.'];
        yield 'text after a separator' => ["0109506000134352\x1DX", 'Element string must start with an AI.'];
        yield 'double separator' => ["0109506000134352\x1D\x1D10A", 'Element string must start with an AI.'];
        yield 'text before the first AI' => ['x(01)09506000134352', 'Element string must start with an AI.'];
        yield 'empty value bracketed' => ['(10)(01)09506000134352', 'Element string has an AI without data.'];
        yield 'empty value unbracketed' => ["0109506000134352\x1D10\x1D", 'Element string has an AI without data.'];
        yield 'GTIN check digit' => ['(01)09506000134353', 'Element string AI data fails a GS1 check of its AI.'];
        yield 'GTIN too short' => ['(01)0950600013435', 'Element string AI data has the wrong length for its AI.'];
        yield 'GTIN too long' => ['(01)095060001343521', 'Element string AI data has the wrong length for its AI.'];
        yield 'predefined AI cut short unbracketed' => ['010950600013435', 'Element string AI data has the wrong length for its AI.'];
        yield 'non-digit in a numeric AI' => ['(01)0950600013435A', 'Element string AI data contains a character outside its GS1 character set.'];
        yield 'batch too long' => ['(01)09506000134352(10)' . \str_repeat('A', 21), 'Element string AI data has the wrong length for its AI.'];
        yield 'character outside CSET 82' => ['(01)09506000134352(10)A#B', 'Element string AI data contains a character outside its GS1 character set.'];
        yield 'raw GS in bracketed data' => ["(01)09506000134352(10)A\x1DB", 'Element string AI data contains a character outside its GS1 character set.'];
        yield 'month 13' => ['(01)09506000134352(17)251301', 'Element string AI data fails a GS1 check of its AI.'];
        yield 'unknown country' => ['(01)09506000134352(422)999', 'Element string AI data fails a GS1 check of its AI.'];
        yield 'unknown currency' => ['(01)09506000134352(3102)000100(3932)000100', 'Element string AI data fails a GS1 check of its AI.'];
        yield 'GIAI without company prefix digits' => ['(8004)095X', 'Element string AI data fails a GS1 check of its AI.'];
        yield 'base64url padding in the middle' => ['(00)095212340005678903(8030)Ab=C', 'Element string AI data contains a character outside its GS1 character set.'];
        yield 'base64url three padding characters' => ['(00)095212340005678903(8030)A===', 'Element string AI data contains a character outside its GS1 character set.'];
        yield 'excluded pair 01 and 02' => ['(01)09506000134352(02)04006381333931', 'Element string has two AIs that must not appear together.'];
        yield 'excluded pair by pattern' => ['(01)09506000134352(3100)000100(3101)000100', 'Element string has two AIs that must not appear together.'];
        yield 'repeated AI with another value' => ['(01)09506000134352(10)A(10)B', 'Element string repeats an AI with a different value.'];
    }

    #[DataProvider('refusedStrings')]
    public function testRefused(string $input, string $message): void
    {
        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage($message);

        ElementString::from($input);
    }

    public function testLostSeparatorAfterAVariableLengthAiIsRefusedNotSplit(): void
    {
        // A serial runs to the next separator: with the separators lost, (21) swallows (91) and (92) and is too long.
        $withSeparators = "]d20104600439931256215Wcd>7\x1D91EE11\x1D92" . \str_repeat('A', 44);
        self::assertSame(['01', '21', '91', '92'], ElementString::from($withSeparators)->ais());

        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string AI data has the wrong length for its AI.');
        ElementString::from(\str_replace("\x1D", '', $withSeparators));
    }

    public function testVariableLengthAiIsNeverCutAtItsMaximumLength(): void
    {
        // Without a separator the batch would have to be split after 20 characters: refused instead.
        $this->expectException(InvalidElementStringException::class);
        ElementString::from('010950600013435210' . \str_repeat('A', 20) . '21XYZ');
    }

    public function testFixedLengthAiThatRequiresASeparatorIsNotSplitEither(): void
    {
        // (422) is N3 but not of predefined length: its data runs to the next separator.
        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string AI data has the wrong length for its AI.');
        ElementString::from('010950600013435242264310');
    }

    public function testDataOfSeveralCarriersBeyondOneQrCodeIsAccepted(): void
    {
        // 2400 x "90X" with a GS after each but the last is 9599 characters, over one GS1 QR Code but not a GS1 rule.
        $element = ElementString::from(\str_repeat('(90)X', 2400));

        self::assertSame(9599, \strlen($element->toBarcodeData()));
    }

    public function testTheBracketedFormIsBoundedAfterParsing(): void
    {
        // 12768 input characters; each "90ABCDEF" + GS gains one character in brackets: 18 + 1417 x 10 = 14188.
        $input = '0109506000134352' . \implode(ElementStringSyntax::GS, \array_fill(0, 1417, '90ABCDEF'));
        self::assertSame(12768, \strlen($input));

        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string must have at most 14178 characters in its bracketed form.');
        ElementString::from($input);
    }

    public function testOverlongInputIsRefusedBeforeParsing(): void
    {
        $this->expectException(InvalidElementStringException::class);
        $this->expectExceptionMessage('Element string must have at most 14178 characters.');
        ElementString::from(\str_repeat('9', 14179));
    }

    public function testSizeBoundsAreInclusive(): void
    {
        // 18 + 1414 x ("(90)" + 6 characters) = 14158; + "(91)" + 16 characters = 14178.
        $bracketed = '(01)09506000134352' . \str_repeat('(90)ABCDEF', 1414) . '(91)' . \str_repeat('A', 16);
        self::assertSame(14178, \strlen($bracketed));
        self::assertSame(14178, \strlen(ElementString::from($bracketed)->value));

        try {
            ElementString::from($bracketed . 'A');
            self::fail('Expected an exception.');
        } catch (InvalidElementStringException $exception) {
            self::assertSame('Element string must have at most 14178 characters.', $exception->getMessage());
        }

        try {
            ElementString::from(\str_repeat('9', 14178));
            self::fail('Expected an exception.');
        } catch (InvalidElementStringException $exception) {
            self::assertStringNotContainsString('14178', $exception->getMessage());
        }
    }

    public function testManyRepeatedAisAreCheckedOnce(): void
    {
        $element = ElementString::from('(01)09506000134352' . \str_repeat('(91)A(3102)000100', 400));

        self::assertSame(['01', '91', '3102'], $element->ais());
        self::assertSame([], $element->unmetRequirements());
    }

    public function testMessagesNeverContainTheInput(): void
    {
        try {
            ElementString::from('(01)09506000134352(10)SECRET#VALUE');
            self::fail('Expected an exception.');
        } catch (InvalidElementStringException $exception) {
            self::assertStringNotContainsString('SECRET', $exception->getMessage());
        }

        try {
            ElementString::from('(7777)SECRET');
            self::fail('Expected an exception.');
        } catch (InvalidElementStringException $exception) {
            self::assertStringNotContainsString('7777', $exception->getMessage());
            self::assertStringNotContainsString('SECRET', $exception->getMessage());
        }
    }

    public function testJsonIsTheCanonicalForm(): void
    {
        self::assertSame('"(01)09506000134352(21)A\\\\(B"', \json_encode(ElementString::from('010950600013435221A(B')));
    }
}
