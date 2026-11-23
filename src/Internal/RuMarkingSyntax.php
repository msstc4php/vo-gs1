<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCodeStructure;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingIdentifier;

/**
 * @internal
 *
 * Russian marking codes and identifiers. GS1-structured codes are read with {@see ElementStringSyntax}, which never
 * splits data whose GS separators were lost: a variable-length group then swallows the next one. Only the last group
 * of an unbracketed code can have done so (brackets and GS separators prove every other boundary); it is refused when
 * it has an unpublished length and the rest after one of its published lengths parses as the AI a published structure
 * puts next, with valid data (see {@see PUBLISHED_NEXT}). Otherwise a code that fits no published structure is
 * accepted as {@see MarkingCodeStructure::GenericGs1} on the AI table's checks alone.
 *
 * @see docs/sources/GS1.md "Russian marking"
 */
final class RuMarkingSyntax
{
    /** Package bound: no combination of the published groups (serial 20, AI (92) of 88, weight, GS) reaches 150 characters. */
    public const int MAX_LENGTH = 255;

    private const string CODE = 'Russian marking code';

    private const string IDENTIFIER = 'Russian marking identifier';

    /** Decree No. 224, p. 43: 14 + 7 + 4 + 4 characters; the first three groups are the identification code. */
    private const string PACK = '/^([0-9]{14})(.{7})(.{4})(.{4})$/sD';

    private const string PACK_IDENTIFIER = '/^([0-9]{14})(.{7})(.{4})$/sD';

    private const int PACK_LENGTH = 29;

    private const int PACK_IDENTIFIER_LENGTH = 25;

    /** @var non-empty-list<positive-int> */
    private const array STANDARD_SERIALS = [6, 13, 20];

    /** @var non-empty-list<positive-int> every published serial length occurs in the shortened structure */
    private const array SHORTENED_SERIALS = [6, 7, 13, 20];

    private const int TOBACCO_GROUP_SERIAL = 7;

    /** @var non-empty-list<positive-int> */
    private const array STANDARD_CODE_LENGTHS = [44, 88];

    private const int KEY_LENGTH = 4;

    /** @var list<non-empty-string> weight (dairy, meat) after AI (92) */
    private const array STANDARD_EXTRAS = ['3103'];

    /** @var list<non-empty-string> weight (dairy, meat) or volume (beer) after AI (93) */
    private const array SHORTENED_EXTRAS = ['3103', '3350', '3351', '3352', '3353', '3354', '3355'];

    /**
     * Per AI: its published lengths and the AIs a published structure puts after it. Finding one of those AIs at one
     * of those lengths inside the data means the GS separator between them was lost.
     *
     * @var array<non-empty-string, array{non-empty-list<positive-int>, non-empty-list<non-empty-string>}>
     */
    private const array PUBLISHED_NEXT = [
        'ai21' => [self::SHORTENED_SERIALS, ['91', '93', '8005']],
        'ai91' => [[self::KEY_LENGTH], ['92']],
        'ai92' => [self::STANDARD_CODE_LENGTHS, self::STANDARD_EXTRAS],
        'ai93' => [[self::KEY_LENGTH], self::SHORTENED_EXTRAS],
    ];

    /** Data Matrix ECC 200 without FNC1 in first position: never a GS1 element string (GenSpecs Table 5-2 has `]d2`). */
    private const string NON_GS1_DATA_MATRIX = ']d1';

    /** GS1 DataMatrix (GenSpecs Table 5-2): never the tobacco pack, which Decree No. 224 encodes without FNC1. */
    private const string GS1_DATA_MATRIX = ']d2';

    /** @var non-empty-list<non-empty-string> prefixes a scanner may put before the pack code */
    private const array PACK_PREFIXES = [self::NON_GS1_DATA_MATRIX, self::GS1_DATA_MATRIX, ElementStringSyntax::GS];

    public static function code(string $input): RuMarkingParts
    {
        self::checkLength($input, self::CODE);
        $pack = self::stripPackPrefix($input);
        if (self::isPackLike($pack)) {
            return self::pack($pack, \str_starts_with($input, self::NON_GS1_DATA_MATRIX), \str_starts_with($input, self::GS1_DATA_MATRIX));
        }

        return self::gs1Code(self::elements($input, self::CODE), self::isBracketed($input));
    }

    /**
     * The tobacco consumer-pack reading only: the canonical value of a pack that also reads as a GS1 code with lost
     * separators keeps the `]d1` symbology identifier, which rules the GS1 reading out.
     */
    public static function tobaccoConsumerPack(string $input): RuMarkingParts
    {
        $pack = self::stripPackPrefix($input);
        if (!self::isPackLike($pack)) {
            throw new InvalidMarkingCodeException(self::CODE . ' (tobacco consumer pack) must have 29 characters, 14 digits first, and no GS.');
        }

        return self::pack($pack, true);
    }

    /**
     * The canonical forms only: the 25 pack characters or the bracketed element string.
     */
    public static function identifier(string $input): RuMarkingParts
    {
        self::checkLength($input, self::IDENTIFIER);
        if (self::isBracketed($input)) {
            return self::gs1Identifier(self::elements($input, self::IDENTIFIER), true);
        }

        if (self::isPackShaped($input, self::PACK_IDENTIFIER_LENGTH)) {
            try {
                return self::packIdentifier($input);
            } catch (InvalidMarkingCodeException $exception) {
                throw new InvalidMarkingCodeException($exception->getMessage() . ' 25 characters are read as a pack КИ; read the document form 01…21… with fromDocumentForm().', $exception->getCode(), previous: $exception);
            }
        }

        throw new InvalidMarkingCodeException(self::IDENTIFIER . ' must be the 25 pack characters or the bracketed form (01)…(21)…; read the document form 01…21… with fromDocumentForm().');
    }

    /**
     * The operator's document form `01…21…` (GS before AI (8005)) or the 25 pack characters.
     *
     * @throws AmbiguousValueException when 25 characters read both as a pack КИ and as `01` + GTIN + `21` + 7
     */
    public static function documentForm(string $input): RuMarkingParts
    {
        self::checkLength($input, self::IDENTIFIER);
        $candidates = [];
        $packFailure = null;
        if (self::isPackShaped($input, self::PACK_IDENTIFIER_LENGTH)) {
            try {
                $candidates[] = self::packIdentifier($input);
            } catch (InvalidMarkingCodeException $exception) {
                $packFailure = $exception;
            }
        }

        try {
            $candidates[] = self::gs1Identifier(self::elements($input, self::IDENTIFIER), self::isBracketed($input));
        } catch (InvalidMarkingCodeException $exception) {
            if ($candidates === []) {
                throw $packFailure ?? $exception;
            }
        }

        if (\count($candidates) > 1) {
            throw new AmbiguousValueException(self::IDENTIFIER . ' document form (both a tobacco consumer pack КИ and AI (01) + (21))', [MarkingIdentifier::class]);
        }

        return $candidates[0];
    }

    private static function checkLength(string $input, string $type): void
    {
        if ($input === '') {
            throw new InvalidMarkingCodeException($type . ' is empty.');
        }

        if (\strlen($input) > self::MAX_LENGTH) {
            throw new InvalidMarkingCodeException(\sprintf('%s must have at most %d characters.', $type, self::MAX_LENGTH));
        }
    }

    private static function stripPackPrefix(string $input): string
    {
        foreach (self::PACK_PREFIXES as $prefix) {
            if (\str_starts_with($input, $prefix)) {
                return \substr($input, \strlen($prefix));
            }
        }

        return $input;
    }

    /**
     * 29 characters starting with 14 digits and without FNC1 (GS or `^`): no GS1-structured code fits, every one
     * needs a separator after its variable-length AI (21).
     */
    private static function isPackLike(string $input): bool
    {
        return \strlen($input) === self::PACK_LENGTH
            && \strspn($input, Gs1Charsets::DIGITS, 0, 14) === 14
            && \strcspn($input, ElementStringSyntax::GS . '^') === self::PACK_LENGTH;
    }

    private static function isPackShaped(string $input, int $length): bool
    {
        return \strlen($input) === $length
            && \strspn($input, Gs1Charsets::DIGITS, 0, 14) === 14
            && \strspn($input, Gs1Charsets::CSET_82, 14) === $length - 14;
    }

    /**
     * @param bool $nonGs1 the input came as `]d1` data, or through {@see tobaccoConsumerPack()}: no GS1 reading
     * @param bool $gs1 the input came as `]d2` data: no pack reading where a GS1 one competes
     *
     * @throws AmbiguousValueException when the 29 characters are also `01` + GTIN + `21` + 5 + `93` + 4 without GS
     */
    private static function pack(string $input, bool $nonGs1, bool $gs1 = false): RuMarkingParts
    {
        if (!self::isPackShaped($input, self::PACK_LENGTH)) {
            throw new InvalidMarkingCodeException(self::CODE . ' (tobacco consumer pack) must have characters of GS1 character set 82 after the GTIN.');
        }

        $lostSeparators = \str_starts_with($input, '01')
            && Gs1CheckDigit::isValid(\substr($input, 2, 14))
            && \substr($input, 16, 2) === '21'
            && \substr($input, 23, 2) === '93';
        if ($lostSeparators && !$nonGs1) {
            if ($gs1) {
                throw new InvalidMarkingCodeException(self::CODE . ' is GS1 DataMatrix data (]d2), so not a tobacco consumer pack, and reads as AI (01), (21) and (93) whose GS separator was lost.');
            }

            if (Gs1CheckDigit::isValid(\substr($input, 0, 14))) {
                throw new AmbiguousValueException(self::CODE . ' (both a tobacco consumer pack and AI (01), (21), (93) with lost GS separators; build the pack with MarkingCode::fromTobaccoConsumerPack())', [MarkingCode::class]);
            }

            throw new InvalidMarkingCodeException(self::CODE . ' reads as AI (01), (21) and (93) whose GS separator was lost.');
        }

        if (\preg_match(self::PACK, $input, $match) !== 1) {
            throw new LogicException('Russian marking code lost its pack groups.');
        }

        self::checkPackGtin($match[1], self::CODE);
        $identifier = $match[1] . $match[2] . $match[3];
        $canonical = $lostSeparators ? self::NON_GS1_DATA_MATRIX . $match[0] : $match[0];

        return new RuMarkingParts(MarkingCodeStructure::TobaccoConsumerPack, $canonical, $match[1], $match[2], null, $match[4], $identifier, $identifier);
    }

    private static function packIdentifier(string $input): RuMarkingParts
    {
        if (\preg_match(self::PACK_IDENTIFIER, $input, $match) !== 1) {
            throw new LogicException('Russian marking identifier lost its pack groups.');
        }

        self::checkPackGtin($match[1], self::IDENTIFIER);

        return new RuMarkingParts(MarkingCodeStructure::TobaccoConsumerPack, $match[0], $match[1], $match[2], null, null, $match[0], $match[0]);
    }

    /**
     * Decree No. 224 puts the same "код товара" into AI (01) of the group pack (p. 44), so it is a GTIN.
     */
    private static function checkPackGtin(string $gtin, string $type): void
    {
        if (!Gs1CheckDigit::isValid($gtin)) {
            throw new InvalidMarkingCodeException($type . ' (tobacco consumer pack) has a wrong GTIN check digit.');
        }
    }

    /**
     * @return non-empty-list<AiElement>
     */
    private static function elements(string $input, string $type): array
    {
        try {
            return ElementStringSyntax::parse($input);
        } catch (InvalidElementStringException $exception) {
            throw new InvalidMarkingCodeException($type . ' is not a valid element string: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
        }
    }

    private static function isBracketed(string $input): bool
    {
        return \str_starts_with($input, '(');
    }

    /**
     * @param non-empty-list<AiElement> $elements
     */
    private static function gs1Code(array $elements, bool $bracketed): RuMarkingParts
    {
        $serial = self::serialAfterKey($elements) ?? throw new InvalidMarkingCodeException(self::CODE . ' must start with AI (01) and AI (21).');
        if (!$bracketed) {
            self::checkLostSeparators($elements, self::CODE);
        }

        [$structure, $key, $code] = self::published($elements) ?? self::generic($elements);
        $identifierElements = \array_slice($elements, 0, $structure === MarkingCodeStructure::TobaccoGroupPack ? 3 : 2);

        return new RuMarkingParts(
            $structure,
            ElementStringSyntax::barcodeData($elements),
            $elements[0]->value,
            $serial->value,
            $key,
            $code,
            ElementStringSyntax::bracketedForm($identifierElements),
            ElementStringSyntax::barcodeData($identifierElements),
        );
    }

    /**
     * @param non-empty-list<AiElement> $elements
     */
    private static function gs1Identifier(array $elements, bool $bracketed): RuMarkingParts
    {
        $count = \count($elements);
        $serial = self::serialAfterKey($elements);
        $tobaccoGroup = $count === 3 && $elements[2]->spec->ai === '8005';
        if (!$serial instanceof AiElement || ($count > 2 && !$tobaccoGroup)) {
            throw new InvalidMarkingCodeException(self::IDENTIFIER . ' must be AI (01) and AI (21), with AI (8005) for a tobacco group pack.');
        }

        if (!$bracketed) {
            self::checkLostSeparators($elements, self::IDENTIFIER);
        }

        $canonical = ElementStringSyntax::bracketedForm($elements);

        return new RuMarkingParts(null, $canonical, $elements[0]->value, $serial->value, null, null, $canonical, ElementStringSyntax::barcodeData($elements));
    }

    /**
     * The AI (21) element when the code starts with AI (01) and AI (21).
     *
     * @param non-empty-list<AiElement> $elements
     */
    private static function serialAfterKey(array $elements): ?AiElement
    {
        $serial = $elements[1] ?? null;

        return $serial !== null && $elements[0]->spec->ai === '01' && $serial->spec->ai === '21' ? $serial : null;
    }

    /**
     * Only the last group of unbracketed data ends without a separator, so only it can have swallowed a lost one.
     *
     * @param non-empty-list<AiElement> $elements
     */
    private static function checkLostSeparators(array $elements, string $type): void
    {
        $last = $elements[\count($elements) - 1];
        [$lengths, $next] = self::PUBLISHED_NEXT['ai' . $last->spec->ai] ?? [[], []];
        if (\in_array(\strlen($last->value), $lengths, true)) {
            return;
        }

        foreach ($lengths as $length) {
            $rest = \substr($last->value, $length);
            foreach ($next as $ai) {
                if (\str_starts_with($rest, $ai) && self::startsWithValidAi($rest, $ai)) {
                    throw new InvalidMarkingCodeException($type . ' has a last AI that runs into the next AI of a published structure at a published length: a GS separator was lost.');
                }
            }
        }
    }

    private static function startsWithValidAi(string $data, string $ai): bool
    {
        try {
            return ElementStringSyntax::parse($data)[0]->spec->ai === $ai;
        } catch (InvalidElementStringException) {
            return false;
        }
    }

    /**
     * @param non-empty-list<AiElement> $elements
     *
     * @return ?array{MarkingCodeStructure, ?non-empty-string, non-empty-string}
     */
    private static function published(array $elements): ?array
    {
        $serialLength = \strlen($elements[1]->value ?? '');
        $third = $elements[2] ?? null;
        $fourth = $elements[3] ?? null;
        if (!$third instanceof AiElement) {
            return null;
        }

        if ($third->spec->ai === '93') {
            return \in_array($serialLength, self::SHORTENED_SERIALS, true)
                && \strlen($third->value) === self::KEY_LENGTH
                && self::extrasPublished(\array_slice($elements, 3), self::SHORTENED_EXTRAS)
                ? [MarkingCodeStructure::Shortened, null, $third->value] : null;
        }

        if (!$fourth instanceof AiElement) {
            return null;
        }

        return match ($third->spec->ai) {
            '91' => \in_array($serialLength, self::STANDARD_SERIALS, true)
                && \strlen($third->value) === self::KEY_LENGTH
                && $fourth->spec->ai === '92'
                && \in_array(\strlen($fourth->value), self::STANDARD_CODE_LENGTHS, true)
                && self::extrasPublished(\array_slice($elements, 4), self::STANDARD_EXTRAS)
                ? [MarkingCodeStructure::Standard, $third->value, $fourth->value] : null,
            '8005' => $serialLength === self::TOBACCO_GROUP_SERIAL
                && $fourth->spec->ai === '93'
                && \strlen($fourth->value) === self::KEY_LENGTH
                ? [MarkingCodeStructure::TobaccoGroupPack, null, $fourth->value] : null,
            default => null,
        };
    }

    /**
     * At most the one optional group the pages publish. The tobacco group pack takes "произвольное количество
     * дополнительных групп данных" (Decree No. 224, p. 44); further groups make a code generic, not invalid.
     *
     * @param list<AiElement> $extras
     * @param list<non-empty-string> $allowed
     */
    private static function extrasPublished(array $extras, array $allowed): bool
    {
        return $extras === [] || (\count($extras) === 1 && \in_array($extras[0]->spec->ai, $allowed, true));
    }

    /**
     * Intact separators, no published structure: AI (01) and (21) first and a crypto tail somewhere after them.
     *
     * @param non-empty-list<AiElement> $elements
     *
     * @return array{MarkingCodeStructure, ?non-empty-string, non-empty-string}
     */
    private static function generic(array $elements): array
    {
        $values = [];
        foreach ($elements as $element) {
            $values['ai' . $element->spec->ai] ??= $element->value;
        }

        $code = $values['ai92'] ?? $values['ai93'] ?? throw new InvalidMarkingCodeException(self::CODE . ' has no verification code: AI (92) or AI (93).');

        return [MarkingCodeStructure::GenericGs1, $values['ai91'] ?? null, $code];
    }
}
