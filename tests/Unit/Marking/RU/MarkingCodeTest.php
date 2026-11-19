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
        self::assertTrue($identifier->equals(MarkingIdentifier::from('01' . self::GTIN . '215Ab+cDe-fGh.J')));
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
        $identifier = MarkingIdentifier::from('01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500');

        self::assertSame('(01)' . self::GTIN . "(21)5U!Mq'X(8005)012500", (string) $identifier);
        self::assertSame('01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500', $identifier->toDocumentForm());
        self::assertTrue($identifier->equals(MarkingIdentifier::from($identifier->toDocumentForm())));
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
        yield 'shortened, GS after (21) lost' => ['01' . self::GTIN . '215aB3x_93Ab1+', 'Russian marking code has no verification code: AI (21) must be followed by AI (91), (93) or (8005).'];
        yield 'shortened serial 13, GS after (21) lost' => ['01' . self::GTIN . '215Ab+cDe-fGh.J93Ab1+', 'Russian marking code has no verification code: AI (21) must be followed by AI (91), (93) or (8005).'];
        yield 'standard, both GS lost' => ['01' . self::GTIN . '215Ab+cDe-fGh.J91EE1092' . self::CODE_44, 'Russian marking code is not a valid element string: Element string AI (21) data must have 1 to 20 characters, got 65.'];
        yield 'standard, GS after (21) lost' => ['01' . self::GTIN . '215aB3x_91EE10' . self::GS . '92' . self::CODE_44, 'Russian marking code AI (21) must be followed by AI (91), (93) or (8005).'];
        yield 'standard, GS after (91) lost' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE1092' . self::CODE_44, 'Russian marking code AI (91) must have 4 characters, got 50.'];
        yield 'tobacco group, GS after (21) lost' => ['01' . self::GTIN . "215U!Mq'X8005012500" . self::GS . '93Ab1+', 'Russian marking code AI (21) has 17 characters; the shortened structure has 6, 7, 13 or 20.'];
        yield 'dairy weight, GS before (3103) lost' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+3103000350', 'Russian marking code AI (93) must have 4 characters, got 14.'];
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
        yield 'pack with a character outside CSET 82' => [self::GTIN . "5U!Mq'XAbC1Zx9#", 'Russian marking code is not a valid element string: Element string contains an unknown AI.'];
        yield 'no (21) after (01)' => ['01' . self::GTIN . '10ABC' . self::GS . '93Ab1+', 'Russian marking code must start with AI (01) and AI (21).'];
        yield 'not starting with (01)' => ['215aB3x_' . self::GS . '93Ab1+', 'Russian marking code must start with AI (01) and AI (21).'];
        yield 'only (01)' => ['01' . self::GTIN, 'Russian marking code must start with AI (01) and AI (21).'];
        yield 'standard without (92)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10', 'Russian marking code AI (91) must be followed by AI (92).'];
        yield 'standard with (93) after (91)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '93Ab1+', 'Russian marking code AI (91) must be followed by AI (92).'];
        yield 'standard with a short (92)' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92ABC', 'Russian marking code AI (92) must have 44 or 88 characters, got 3.'];
        yield 'standard with serial 7' => ['01' . self::GTIN . '215Ab+cDe' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44, 'Russian marking code AI (21) has 7 characters; the standard structure has 6, 13 or 20.'];
        yield 'standard with an unpublished extra group' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44 . self::GS . '10ABC', 'Russian marking code has a data group the standard structure does not publish after its verification code.'];
        yield 'standard with volume' => ['01' . self::GTIN . '215Ab+cDe-fGh.J' . self::GS . '91EE10' . self::GS . '92' . self::CODE_44 . self::GS . '3353000500', 'Russian marking code has a data group the standard structure does not publish after its verification code.'];
        yield 'shortened with two weights' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+' . self::GS . '3103000350' . self::GS . '3353000500', 'Russian marking code has a data group the shortened structure does not publish after its verification code.'];
        yield 'shortened with a short (93)' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1', 'Russian marking code AI (93) must have 4 characters, got 3.'];
        yield 'tobacco group with serial 6' => ['01' . self::GTIN . '215aB3x_' . self::GS . '8005012500' . self::GS . '93Ab1+', 'Russian marking code AI (21) has 6 characters; the tobacco group pack structure has 7.'];
        yield 'tobacco group without (93)' => ['01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500', 'Russian marking code AI (8005) must be followed by AI (93).'];
        yield 'tobacco group with a short (93)' => ['01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500' . self::GS . '93Ab1', 'Russian marking code AI (93) must have 4 characters, got 3.'];
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

    public function testStructureLabels(): void
    {
        self::assertSame(['tobacco consumer pack', 'tobacco group pack', 'standard', 'shortened'], \array_map(static fn (MarkingCodeStructure $structure): string => $structure->label(), MarkingCodeStructure::cases()));
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

    public function testAn25CharacterIdentifierFittingBothFormsIsAmbiguous(): void
    {
        // "01" + 12 digits of this GTIN is itself a valid GTIN-14, so the 25 characters also read as a pack КИ.
        $value = '0104600439930501' . "215U!Mq'X";

        try {
            MarkingIdentifier::from($value);
            self::fail('Expected AmbiguousValueException.');
        } catch (AmbiguousValueException $exception) {
            self::assertSame([MarkingIdentifier::class], $exception->candidates());
            self::assertSame('Ambiguous Russian marking identifier (tobacco consumer pack or AI (01) and (21)): the value fits MarkingIdentifier.', $exception->getMessage());
            self::assertStringNotContainsString('04600439930501', $exception->getMessage());
        }

        self::assertSame('04600439930501', (string) MarkingIdentifier::from("(01)04600439930501(21)5U!Mq'X")->gtin());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidIdentifiers(): iterable
    {
        yield 'empty' => ['', 'Russian marking identifier is empty.'];
        yield 'too long' => [\str_repeat('1', 256), 'Russian marking identifier must have at most 255 characters, got 256.'];
        yield 'with crypto tail' => ['01' . self::GTIN . '215aB3x_' . self::GS . '93Ab1+', 'Russian marking identifier must be AI (01) and AI (21), with AI (8005) for a tobacco group pack.'];
        yield 'serial of 8' => ['01' . self::GTIN . '215aB3x_YZ', 'Russian marking identifier AI (21) has 8 characters; the published structures have 6, 7, 13 or 20.'];
        yield 'four groups' => ['01' . self::GTIN . "215U!Mq'X" . self::GS . '8005012500' . self::GS . '93Ab1+', 'Russian marking identifier must be AI (01) and AI (21), with AI (8005) for a tobacco group pack.'];
        yield 'tobacco group serial of 6' => ['01' . self::GTIN . '215aB3x_' . self::GS . '8005012500', 'Russian marking identifier AI (21) has 6 characters; the tobacco group pack structure has 7.'];
        yield 'pack with a wrong check digit' => ['04600439931257' . "5U!Mq'XAbC1", 'Russian marking identifier (tobacco consumer pack) has a wrong GTIN check digit.'];
        yield 'garbage' => ['ABC', 'Russian marking identifier is not a valid element string: Element string must start with an AI.'];
    }

    #[DataProvider('invalidIdentifiers')]
    public function testInvalidIdentifiersAreRefused(string $value, string $message): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage($message);

        MarkingIdentifier::from($value);
    }
}
