<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Marking\RU;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiCharset;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Internal\RuMarkingParts;
use MSSTC4PHP\ValueObject\Gs1\Internal\RuMarkingSyntax;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCodeStructure;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkingCode::class)]
#[CoversClass(MarkingIdentifier::class)]
#[CoversClass(MarkingCodeStructure::class)]
#[CoversClass(RuMarkingSyntax::class)]
#[CoversClass(RuMarkingParts::class)]
#[UsesClass(ElementString::class)]
#[UsesClass(ElementStringSyntax::class)]
#[UsesClass(AiElement::class)]
#[UsesClass(AiLinters::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(AiCharset::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(Gs1Dates::class)]
#[UsesClass(Gs1Charsets::class)]
#[UsesClass(KeyRules::class)]
#[UsesClass(Gtin::class)]
final class MarkingCodeTest extends TestCase
{
    private const string GS = "\x1D";

    private const string GTIN = '04600439931256';

    /** 44 characters: the length of AI (92) in the decrees' standard structure. */
    private const string CODE_44 = 'MEUCIQDvA1b2C3d4E5f6G7h8I9j0K+L/M=N-O.P_Q:R;';

    /**
     * One code per published structure, built from the format line of the operator's page or the decree (none of
     * them prints a real code).
     *
     * @return iterable<string, array{string, MarkingCodeStructure, string, string|null, string, string}>
     */
    public static function publishedStructures(): iterable
    {
        // Decree No. 224 of 28.02.2019, p. 43: 14 digits + 7 + 4 (encoded price) + 4 (check code), no AIs.
        yield 'tobacco consumer pack (224, p. 43)' => [self::GTIN . "5U!Mq'X" . 'AbC1Zx9+', MarkingCodeStructure::TobaccoConsumerPack, "5U!Mq'X", null, 'Zx9+', self::GTIN . "5U!Mq'XAbC1"];
        // Decree No. 224, p. 44: (01) 14, (21) 7, (8005) 6, (93) 4.
        yield 'tobacco group pack (224, p. 44)' => ['01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500' . self::GS . '93Ab1+', MarkingCodeStructure::TobaccoGroupPack, "5U!Mq'X", null, 'Ab1+', '(01)' . self::GTIN . "(21)5U!Mq'X(8005)012500"];
        // Decree No. 1956 of 31.12.2019, p. 30 (light industry): (21) 13, (91) 4, (92) 44.
        yield 'standard, serial 13 (1956, p. 30)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44, MarkingCodeStructure::Standard, '5Ab+cDe-fGh.J', 'EE10', self::CODE_44, '(01)' . self::GTIN . '(21)5Ab+cDe-fGh.J'];
        // Decree No. 677 of 27.05.2024, pp. 49-50 (canned food): (21) 6 in the long structure.
        yield 'standard, serial 6 (677, p. 49)' => ['01' . self::GTIN . '215aB3x_' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44, MarkingCodeStructure::Standard, '5aB3x_', 'EE10', self::CODE_44, '(01)' . self::GTIN . '(21)5aB3x_'];
        // Decree No. 1953 of 31.12.2019, p. 33 (cameras): (21) 20.
        yield 'standard, serial 20 (1953, p. 33)' => ['01' . self::GTIN . '2151234567890ABCDEFGHI' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44, MarkingCodeStructure::Standard, '51234567890ABCDEFGHI', 'EE10', self::CODE_44, '(01)' . self::GTIN . '(21)51234567890ABCDEFGHI'];
        // Decree No. 860 of 05.07.2019, p. 38 (footwear): (92) 88.
        yield 'standard, code 88 (860, p. 38)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44 . self::CODE_44, MarkingCodeStructure::Standard, '5Ab+cDe-fGh.J', 'EE10', self::CODE_44 . self::CODE_44, '(01)' . self::GTIN . '(21)5Ab+cDe-fGh.J'];
        // Decree No. 205 of 28.02.2026, pp. 51-52 (meat): optional (3103) after (92).
        yield 'standard with weight (205, p. 51)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44 . self::GS . '3103001250', MarkingCodeStructure::Standard, '5Ab+cDe-fGh.J', 'EE10', self::CODE_44, '(01)' . self::GTIN . '(21)5Ab+cDe-fGh.J'];
        // Decree No. 2099 of 15.12.2020, p. 46 (dairy): (21) 6, (93) 4.
        yield 'shortened, serial 6 (2099, p. 46)' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+', MarkingCodeStructure::Shortened, '5aB3x_', null, 'Ab1+', '(01)' . self::GTIN . '(21)5aB3x_'];
        // Decree No. 2099, p. 46 г: optional (3103) after (93).
        yield 'shortened with weight (2099, p. 46)' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+' . self::GS . '3103000350', MarkingCodeStructure::Shortened, '5aB3x_', null, 'Ab1+', '(01)' . self::GTIN . '(21)5aB3x_'];
        // Decree No. 2173 of 30.11.2022, p. 45 (beer): (21) 7, optional (335y) after (93).
        yield 'shortened, serial 7, volume (2173, p. 45)' => ['01' . self::GTIN . '215aB3x_Y' . self::GS . '93Ab1+' . self::GS . '3353000500', MarkingCodeStructure::Shortened, '5aB3x_Y', null, 'Ab1+', '(01)' . self::GTIN . '(21)5aB3x_Y'];
        // Decree No. 841 of 31.05.2021, p. 45 (water): (21) 13.
        yield 'shortened, serial 13 (841, p. 45)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '93Ab1+', MarkingCodeStructure::Shortened, '5Ab+cDe-fGh.J', null, 'Ab1+', '(01)' . self::GTIN . '(21)5Ab+cDe-fGh.J'];
        // Decree No. 1954 of 28.11.2025, pp. 46-47 (radio-electronics): (21) 20 in the shortened structure.
        // Published lengths with intact GS: AIs that happen to occur inside the serial are data, not a lost separator.
        yield 'shortened, serial 20 with "93" at 13' => ['01' . self::GTIN . '21512345678901293ABCDE' . self::GS . '93Ab1+', MarkingCodeStructure::Shortened, '512345678901293ABCDE', null, 'Ab1+', '(01)' . self::GTIN . '(21)512345678901293ABCDE'];
        yield 'shortened, serial 13 with "8005" at 7' => ['01' . self::GTIN . '215abcdef8005xy' . self::GS . '93Ab1+', MarkingCodeStructure::Shortened, '5abcdef8005xy', null, 'Ab1+', '(01)' . self::GTIN . '(21)5abcdef8005xy'];
        yield 'standard, serial 6 with "91"' => ['01' . self::GTIN . '21591ab_' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44, MarkingCodeStructure::Standard, '591ab_', 'EE10', self::CODE_44, '(01)' . self::GTIN . '(21)591ab_'];
        yield 'standard, (91) and (93) inside a 44-character (92)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . \substr(self::CODE_44, 0, 40) . '3103', MarkingCodeStructure::Standard, '5Ab+cDe-fGh.J', 'EE10', \substr(self::CODE_44, 0, 40) . '3103', '(01)' . self::GTIN . '(21)5Ab+cDe-fGh.J'];
        yield 'shortened, serial 20 (1954, p. 46)' => ['01' . self::GTIN . '2151234567890ABCDEFGHI' . self::GS . '93Ab1+', MarkingCodeStructure::Shortened, '51234567890ABCDEFGHI', null, 'Ab1+', '(01)' . self::GTIN . '(21)51234567890ABCDEFGHI'];
    }

    #[DataProvider('publishedStructures')]
    public function testEveryPublishedStructureParses(string $value, MarkingCodeStructure $structure, string $serial, ?string $key, string $code, string $identifier): void
    {
        $marking = MarkingCode::from($value);

        self::assertSame($value, (string) $marking);
        self::assertSame($structure, $marking->structure());
        self::assertSame(self::GTIN, (string) $marking->gtin());
        self::assertSame($serial, $marking->serial());
        self::assertSame($key, $marking->verificationKey());
        self::assertSame($code, $marking->verificationCode());
        self::assertSame($identifier, (string) $marking->identifier());
        self::assertSame($structure !== MarkingCodeStructure::TobaccoConsumerPack, $marking->elementString() instanceof ElementString);
        self::assertTrue(MarkingCode::from((string) $marking)->equals($marking));
        self::assertSame((string) $marking, (string) MarkingCode::from((string) $marking));
    }

    public function testIdentifierDropsTheCryptoTailAndKeepsGtinAndSerial(): void
    {
        $identifier = MarkingCode::from('01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44)->identifier();

        self::assertSame(self::GTIN, (string) $identifier->gtin());
        self::assertSame('5Ab+cDe-fGh.J', $identifier->serial());
        self::assertSame('01' . self::GTIN . '215Ab+cDe-fGh.J', $identifier->toDocumentForm());
        self::assertTrue($identifier->equals(MarkingIdentifier::fromDocumentForm('01' . self::GTIN . '215Ab+cDe-fGh.J')));
        self::assertFalse($identifier->isTobaccoConsumerPack());
    }

    public function testTheTobaccoConsumerPackIdentifierIsTheFirstThreeGroups(): void
    {
        $identifier = MarkingCode::from(self::GTIN . "5U!Mq'XAbC1Zx9+")->identifier();

        self::assertTrue($identifier->isTobaccoConsumerPack());
        self::assertSame(self::GTIN . "5U!Mq'XAbC1", (string) $identifier);
        self::assertSame(self::GTIN . "5U!Mq'XAbC1", $identifier->toDocumentForm());
        self::assertSame("5U!Mq'X", $identifier->serial());
        self::assertSame(self::GTIN, (string) $identifier->gtin());
        self::assertTrue($identifier->equals(MarkingIdentifier::from((string) $identifier)));
    }

    public function testTheTobaccoGroupIdentifierKeepsItsPriceGroupAndItsSeparator(): void
    {
        $identifier = MarkingIdentifier::fromDocumentForm('01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500');

        self::assertSame('(01)' . self::GTIN . "(21)5U!Mq'X(8005)012500", (string) $identifier);
        self::assertSame('01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500', $identifier->toDocumentForm());
        self::assertTrue($identifier->equals(MarkingIdentifier::fromDocumentForm($identifier->toDocumentForm())));
    }

    public function testScannerOutputWithSymbologyIdentifierIsTheSameCode(): void
    {
        $plain = MarkingCode::from('01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+');

        self::assertTrue(MarkingCode::from(']d2' . $plain)->equals($plain));
        self::assertTrue(MarkingCode::from(self::GS . $plain)->equals($plain));
        self::assertTrue(MarkingCode::from('(01)' . self::GTIN . '(21)5aB3x_(93)Ab1+')->equals($plain));
        self::assertTrue(MarkingCode::from($plain . self::GS)->equals($plain));
        self::assertTrue(MarkingCode::from(']d2' . self::GTIN . "5U!Mq'XAbC1Zx9+")->equals(MarkingCode::from(self::GTIN . "5U!Mq'XAbC1Zx9+")));
    }

    public function testCanonicalFormKeepsTheGsSeparatorsAndAParenthesisInData(): void
    {
        $code = MarkingCode::from('(01)' . self::GTIN . '(21)5aB\(x_(91)EE10(92)' . self::CODE_44);

        self::assertSame('01' . self::GTIN . '215aB(x_' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44, (string) $code);
        self::assertSame(2, \substr_count((string) $code, self::GS));
        self::assertSame((string) $code, (string) MarkingCode::from((string) $code));
        self::assertSame((string) $code, \json_decode((string) \json_encode($code)));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function lostSeparators(): iterable
    {
        yield 'shortened, GS after (21) lost' => ['01' . self::GTIN . '215aB3x_93Ab1+', 'Russian marking code AI (21) runs into AI (93) at a published length: a GS separator was lost.'];
        yield 'shortened serial 13, GS after (21) lost' => ['01' . self::GTIN . '215Ab+cDe-fGh.J93Ab1+', 'Russian marking code AI (21) runs into AI (93) at a published length: a GS separator was lost.'];
        yield 'standard, both GS lost' => ['01' . self::GTIN . '215Ab+cDe-fGh.J91EE1092' . self::CODE_44, 'Russian marking code is not a valid element string: Element string AI (21) data must have 1 to 20 characters, got 65.'];
        yield 'standard, GS after (21) lost' => ['01' . self::GTIN . '215aB3x_91EE10' . self::GS . '92' . self::CODE_44, 'Russian marking code AI (21) runs into AI (91) at a published length: a GS separator was lost.'];
        yield 'standard, GS after (91) lost' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE1092' . self::CODE_44, 'Russian marking code AI (91) runs into AI (92) at a published length: a GS separator was lost.'];
        yield 'tobacco group, GS after (21) lost' => ['01' . self::GTIN . "215U!Mq'X8005012500" . self::GS . '93Ab1+', 'Russian marking code AI (21) runs into AI (8005) at a published length: a GS separator was lost.'];
        yield 'dairy weight, GS before (3103) lost' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+3103000350', 'Russian marking code AI (93) runs into AI (3103) at a published length: a GS separator was lost.'];
        yield '29 characters, GS lost, 5-character serial' => ['010460620308662721abcde93Ab1+', 'Russian marking code reads as AI (01), (21) and (93) whose GS separator was lost.'];
        yield 'serial 6 with a lost GS before (8005)' => ['01' . self::GTIN . '215aB3x_8005012500' . self::GS . '93Ab1+', 'Russian marking code AI (21) runs into AI (8005) at a published length: a GS separator was lost.'];
    }

    #[DataProvider('lostSeparators')]
    public function testACodeWhoseSeparatorsWereLostIsRefusedNotSplit(string $value, string $message): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage($message);

        MarkingCode::from($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidCodes(): iterable
    {
        yield 'empty' => ['', 'Russian marking code is empty.'];
        yield 'too long' => [\str_repeat('1', 256), 'Russian marking code must have at most 255 characters, got 256.'];
        yield 'at the length bound' => [\str_repeat('1', 255), 'Russian marking code is not a valid element string: Element string AI (11) data must have 6 characters, got 5.'];
        yield 'pack-shaped but starting with a letter' => ['X4600439931256' . "5U!Mq'XAbC1Zx9+", 'Russian marking code is not a valid element string: Element string must start with an AI.'];
        yield 'pack with a wrong GTIN check digit' => ['04600439931257' . "5U!Mq'XAbC1Zx9+", 'Russian marking code (tobacco consumer pack) has a wrong GTIN check digit.'];
        yield 'pack with a character outside CSET 82' => [self::GTIN . "5U!Mq'XAbC1Zx9#", 'Russian marking code (tobacco consumer pack) must have characters of GS1 character set 82 after the GTIN.'];
        yield 'no (21) after (01)' => ['01' . self::GTIN . '10ABC' . self::GS . '93Ab1+', 'Russian marking code must start with AI (01) and AI (21).'];
        yield 'not starting with (01)' => ['215aB3x_' . self::GS . '93Ab1+', 'Russian marking code must start with AI (01) and AI (21).'];
        yield 'identifier only' => ['01' . self::GTIN . '215aB3x_', 'Russian marking code has no verification code: AI (92) or AI (93).'];
        yield 'only (01)' => ['01' . self::GTIN, 'Russian marking code must start with AI (01) and AI (21).'];
        yield 'standard without (92)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10', 'Russian marking code has no verification code: AI (92) or AI (93).'];
        yield 'tobacco group without (93)' => ['01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500', 'Russian marking code has no verification code: AI (92) or AI (93).'];
    }

    #[DataProvider('invalidCodes')]
    public function testInvalidCodesAreRefusedWithoutEchoingTheInput(string $value, string $message): void
    {
        try {
            MarkingCode::from($value);
            self::fail('Expected InvalidMarkingCodeException.');
        } catch (InvalidMarkingCodeException $exception) {
            self::assertSame($message, $exception->getMessage());
        }
    }

    /**
     * @return iterable<string, array{string, string, string|null, string}>
     */
    public static function genericCodes(): iterable
    {
        yield 'serial of 8, GS, (93)' => ['01' . self::GTIN . '215aB3x_YZ' . self::GS . '93Ab1+', '5aB3x_YZ', null, 'Ab1+'];
        yield 'shortened with a further group' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+' . self::GS . '240ABC', '5aB3x_', null, 'Ab1+'];
        yield 'standard with serial 7' => ['01' . self::GTIN . '215Ab+cDe' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44, '5Ab+cDe', 'EE10', self::CODE_44];
        yield 'standard with a short (92)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92ABC', '5Ab+cDe-fGh.J', 'EE10', 'ABC'];
        yield 'standard with (93) after (91)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '93Ab1+', '5Ab+cDe-fGh.J', 'EE10', 'Ab1+'];
        yield 'standard with volume' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44 . self::GS . '3353000500', '5Ab+cDe-fGh.J', 'EE10', self::CODE_44];
        yield 'shortened with two weights' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+' . self::GS . '31030003503353000500', '5aB3x_', null, 'Ab1+'];
        yield 'shortened with a short (93)' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1', '5aB3x_', null, 'Ab1'];
        yield 'tobacco group with serial 6' => ['01' . self::GTIN . '215aB3x_' . self::GS . '8005012500' . self::GS . '93Ab1+', '5aB3x_', null, 'Ab1+'];
        yield 'separator and caret, 29 characters' => ['01' . self::GTIN . '215aB3' . self::GS . '93Ab1+', '5aB3', null, 'Ab1+'];
        yield 'another AI between serial and tail' => ['01' . self::GTIN . '215aB3x_' . self::GS . '240ABC' . self::GS . '93Ab1+', '5aB3x_', null, 'Ab1+'];
        yield '(92) wins over (93)' => ['01' . self::GTIN . '215aB3x_' . self::GS . '92ABC' . self::GS . '93Ab1+', '5aB3x_', null, 'ABC'];
        yield 'tobacco group with a short (93)' => ['01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500' . self::GS . '93Ab1', "5U!Mq'X", null, 'Ab1'];
    }

    #[DataProvider('genericCodes')]
    public function testIntactSeparatorsOutsideThePublishedStructuresAreGeneric(string $value, string $serial, ?string $key, string $code): void
    {
        $marking = MarkingCode::from($value);

        self::assertSame(MarkingCodeStructure::GenericGs1, $marking->structure());
        self::assertSame($value, (string) $marking);
        self::assertSame($serial, $marking->serial());
        self::assertSame($key, $marking->verificationKey());
        self::assertSame($code, $marking->verificationCode());
        self::assertSame('(01)' . self::GTIN . '(21)' . $serial, (string) $marking->identifier());
        self::assertSame((string) $marking, (string) MarkingCode::from((string) $marking));
    }

    public function testA29CharacterStringFittingThePackAndALostSeparatorCodeIsAmbiguous(): void
    {
        try {
            MarkingCode::from('010460043993050121abcde93Ab1+');
            self::fail('Expected AmbiguousValueException.');
        } catch (AmbiguousValueException $exception) {
            self::assertSame([MarkingCode::class], $exception->candidates());
            self::assertSame('Ambiguous Russian marking code (both a tobacco consumer pack and AI (01), (21), (93) with lost GS separators): the value fits MarkingCode.', $exception->getMessage());
        }
    }

    public function testCaretNotationOf29CharactersIsNotAPack(): void
    {
        self::assertSame(MarkingCodeStructure::GenericGs1, MarkingCode::from('01' . self::GTIN . '215aB3^93Ab1+')->structure());
    }

    public function testPacksThatOnlyPartlyLookLikeALostSeparatorCodeArePacks(): void
    {
        // Starts like "01" + GTIN, but no "21" where the serial AI would stand.
        self::assertSame(MarkingCodeStructure::TobaccoConsumerPack, MarkingCode::from('0104600439930501XXabcde93Ab1+')->structure());
        // "21" and "93" in place, but the pack does not start with "01".
        self::assertSame(MarkingCodeStructure::TobaccoConsumerPack, MarkingCode::from(self::GTIN . 'xy21abcdd93Zx9+')->structure());
    }

    public function testThePackIdentifierNeverThrowsEvenWhenItAlsoReadsAsADocumentForm(): void
    {
        $code = MarkingCode::from("0104600439930501215U!Mq'XZx9+");

        self::assertSame(MarkingCodeStructure::TobaccoConsumerPack, $code->structure());
        self::assertSame("0104600439930501215U!Mq'X", (string) $code->identifier());
        self::assertTrue($code->identifier()->isTobaccoConsumerPack());
    }

    public function testStructureLabels(): void
    {
        self::assertSame(['tobacco consumer pack', 'tobacco group pack', 'standard', 'shortened', 'generic GS1'], \array_map(static fn (MarkingCodeStructure $structure): string => $structure->label(), MarkingCodeStructure::cases()));
    }

    public function testTheTobaccoGroupAcceptsAnyFurtherGroup(): void
    {
        $code = MarkingCode::from('01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500' . self::GS . '93Ab1+' . self::GS . '240ABC');

        self::assertSame(MarkingCodeStructure::TobaccoGroupPack, $code->structure());
        self::assertSame('ABC', $code->elementString()?->get('240'));
    }

    public function testAWrappedElementStringFailureKeepsItsCause(): void
    {
        try {
            MarkingCode::from('0104600439931257215aB3x_' . self::GS . '93Ab1+');
            self::fail('Expected InvalidMarkingCodeException.');
        } catch (InvalidMarkingCodeException $exception) {
            self::assertStringStartsWith('Russian marking code is not a valid element string: ', $exception->getMessage());
            self::assertNotNull($exception->getPrevious());
        }
    }

    public function testTheDocumentFormIsReadByItsOwnFactory(): void
    {
        $identifier = MarkingIdentifier::fromDocumentForm('01' . self::GTIN . '215Ab+cDe-fGh.J');

        self::assertSame('(01)' . self::GTIN . '(21)5Ab+cDe-fGh.J', (string) $identifier);
        self::assertTrue(MarkingIdentifier::fromDocumentForm(self::GTIN . "5U!Mq'XAbC1")->isTobaccoConsumerPack());
        self::assertSame('(01)04606203086627(21)5aB3x_Y', (string) MarkingIdentifier::fromDocumentForm('0104606203086627215aB3x_Y'));
        self::assertTrue(MarkingIdentifier::fromDocumentForm('(01)' . self::GTIN . '(21)5aB3x_YZ')->equals(MarkingIdentifier::from('(01)' . self::GTIN . '(21)5aB3x_YZ')));
    }

    public function testA25CharacterDocumentFormFittingBothReadingsIsAmbiguous(): void
    {
        // "01" + 12 digits of this GTIN is itself a valid GTIN-14, so the 25 characters also read as a pack КИ.
        $value = '0104600439930501' . "215U!Mq'X";

        try {
            MarkingIdentifier::fromDocumentForm($value);
            self::fail('Expected AmbiguousValueException.');
        } catch (AmbiguousValueException $exception) {
            self::assertSame([MarkingIdentifier::class], $exception->candidates());
            self::assertSame('Ambiguous Russian marking identifier document form (both a tobacco consumer pack КИ and AI (01) + (21)): the value fits MarkingIdentifier.', $exception->getMessage());
        }

        self::assertTrue(MarkingIdentifier::from($value)->isTobaccoConsumerPack());
        self::assertSame('04600439930501', (string) MarkingIdentifier::from("(01)04600439930501(21)5U!Mq'X")->gtin());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidIdentifiers(): iterable
    {
        yield 'empty' => ['', 'Russian marking identifier is empty.'];
        yield 'too long' => [\str_repeat('1', 256), 'Russian marking identifier must have at most 255 characters, got 256.'];
        yield 'document form' => ['01' . self::GTIN . '215aB3x_', 'Russian marking identifier must be the 25 pack characters or the bracketed form (01)…(21)…; read the document form 01…21… with fromDocumentForm().'];
        yield 'with crypto tail' => ['(01)' . self::GTIN . '(21)5aB3x_(93)Ab1+', 'Russian marking identifier must be AI (01) and AI (21), with AI (8005) for a tobacco group pack.'];
        yield 'four groups' => ['(01)' . self::GTIN . "(21)5U!Mq'X(8005)012500(93)Ab1+", 'Russian marking identifier must be AI (01) and AI (21), with AI (8005) for a tobacco group pack.'];
        yield 'tobacco group serial of 6' => ['(01)' . self::GTIN . '(21)5aB3x_(8005)012500', 'Russian marking identifier AI (21) has 6 characters; the tobacco group pack structure has 7.'];
        yield 'serial running into (93)' => ['(01)' . self::GTIN . '(21)5aB3x_93Ab', 'Russian marking identifier AI (21) runs into AI (93) at a published length: a GS separator was lost.'];
        yield 'pack with a wrong check digit' => ['04600439931257' . "5U!Mq'XAbC1", 'Russian marking identifier (tobacco consumer pack) has a wrong GTIN check digit.'];
        yield 'document form whose pack reading fails' => ['0104606203086627215aB3x_Y', 'Russian marking identifier (tobacco consumer pack) has a wrong GTIN check digit. 25 characters are read as a pack КИ; read the document form 01…21… with fromDocumentForm().'];
        yield 'pack-shaped after a letter' => ['X4600439931256' . "5U!Mq'XAbC1", 'Russian marking identifier must be the 25 pack characters or the bracketed form (01)…(21)…; read the document form 01…21… with fromDocumentForm().'];
        yield 'bad bracketed form' => ['(01)04600439931257(21)5aB3x_', 'Russian marking identifier is not a valid element string: Element string AI (01) data fails the GS1 csum check.'];
    }

    #[DataProvider('invalidIdentifiers')]
    public function testInvalidIdentifiersAreRefused(string $value, string $message): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage($message);

        MarkingIdentifier::from($value);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidDocumentForms(): iterable
    {
        yield 'pack with a wrong check digit, no GS1 reading' => ['04600439931257' . "5U!Mq'XAbC1", 'Russian marking identifier (tobacco consumer pack) has a wrong GTIN check digit.'];
        yield 'garbage' => ['ABC', 'Russian marking identifier is not a valid element string: Element string must start with an AI.'];
        yield 'lost GS before (8005)' => ['01' . self::GTIN . "215U!Mq'X8005012500", 'Russian marking identifier AI (21) runs into AI (8005) at a published length: a GS separator was lost.'];
        yield 'too long' => [\str_repeat('1', 256), 'Russian marking identifier must have at most 255 characters, got 256.'];
    }

    #[DataProvider('invalidDocumentForms')]
    public function testInvalidDocumentFormsAreRefused(string $value, string $message): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage($message);

        MarkingIdentifier::fromDocumentForm($value);
    }
}
