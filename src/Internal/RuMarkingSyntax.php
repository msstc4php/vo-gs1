<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCodeStructure;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingIdentifier;

/**
 * @internal
 *
 * The published structures of Russian marking codes. GS1-structured codes are read with
 * {@see ElementStringSyntax}, which never splits data whose GS separators were lost; the fixed lengths of the
 * published groups then refuse a code whose groups ran together.
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

    /** @var non-empty-list<positive-int> */
    private const array STANDARD_SERIALS = [6, 13, 20];

    /** @var non-empty-list<positive-int> every published serial length occurs in the shortened structure */
    private const array SHORTENED_SERIALS = [6, 7, 13, 20];

    /** @var non-empty-list<positive-int> */
    private const array TOBACCO_GROUP_SERIALS = [7];

    /** @var non-empty-list<positive-int> */
    private const array STANDARD_CODE_LENGTHS = [44, 88];

    private const int KEY_LENGTH = 4;

    /** @var list<non-empty-string> weight (dairy, meat) after AI (92) */
    private const array STANDARD_EXTRAS = ['3103'];

    /** @var list<non-empty-string> weight (dairy, meat) or volume (beer) after AI (93) */
    private const array SHORTENED_EXTRAS = ['3103', '3350', '3351', '3352', '3353', '3354', '3355'];

    /** @var non-empty-list<non-empty-string> prefixes a scanner may put before the pack code */
    private const array PACK_PREFIXES = [']d1', ']d2', ElementStringSyntax::GS];

    public static function code(string $input): RuMarkingParts
    {
        self::checkLength($input, self::CODE);
        $pack = self::stripPackPrefix($input);
        if (self::isPackShaped($pack, 29)) {
            return self::pack($pack);
        }

        return self::gs1Code(self::elements($input, self::CODE));
    }

    /**
     * @throws AmbiguousValueException when 25 characters read both as a tobacco pack КИ and as AI (01) + (21)
     */
    public static function identifier(string $input): RuMarkingParts
    {
        self::checkLength($input, self::IDENTIFIER);
        $candidates = [];
        $packFailure = null;
        if (self::isPackShaped($input, 25)) {
            try {
                $candidates[] = self::packIdentifier($input);
            } catch (InvalidMarkingCodeException $exception) {
                $packFailure = $exception;
            }
        }

        try {
            $candidates[] = self::gs1Identifier(self::elements($input, self::IDENTIFIER));
        } catch (InvalidMarkingCodeException $exception) {
            if ($candidates === []) {
                throw $packFailure ?? $exception;
            }
        }

        if (\count($candidates) > 1) {
            throw new AmbiguousValueException(self::IDENTIFIER . ' (tobacco consumer pack or AI (01) and (21))', [MarkingIdentifier::class]);
        }

        return $candidates[0];
    }

    private static function checkLength(string $input, string $type): void
    {
        if ($input === '') {
            throw new InvalidMarkingCodeException($type . ' is empty.');
        }

        if (\strlen($input) > self::MAX_LENGTH) {
            throw new InvalidMarkingCodeException(\sprintf('%s must have at most %d characters, got %d.', $type, self::MAX_LENGTH, \strlen($input)));
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
     * Fourteen digits and CSET 82 for the rest; a GS1-structured code never fits, it carries GS or brackets.
     */
    private static function isPackShaped(string $input, int $length): bool
    {
        return \strlen($input) === $length
            && \strspn($input, Gs1Charsets::DIGITS, 0, 14) === 14
            && \strspn($input, Gs1Charsets::CSET_82, 14) === $length - 14;
    }

    private static function pack(string $input): RuMarkingParts
    {
        if (\preg_match(self::PACK, $input, $match) !== 1) {
            throw new LogicException('Russian marking code lost its pack groups.');
        }

        self::checkPackGtin($match[1], self::CODE);
        $identifier = $match[1] . $match[2] . $match[3];

        return new RuMarkingParts(MarkingCodeStructure::TobaccoConsumerPack, $match[0], $match[1], $match[2], null, $match[4], $identifier, $identifier);
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

    /**
     * @param non-empty-list<AiElement> $elements
     */
    private static function gs1Code(array $elements): RuMarkingParts
    {
        $serial = self::serialAfterKey($elements) ?? throw new InvalidMarkingCodeException(self::CODE . ' must start with AI (01) and AI (21).');

        if (!isset($elements[2])) {
            throw new InvalidMarkingCodeException(self::CODE . ' has no verification code: AI (21) must be followed by AI (91), (93) or (8005).');
        }

        $structure = match ($elements[2]->spec->ai) {
            '91' => MarkingCodeStructure::Standard,
            '93' => MarkingCodeStructure::Shortened,
            '8005' => MarkingCodeStructure::TobaccoGroupPack,
            default => throw new InvalidMarkingCodeException(self::CODE . ' AI (21) must be followed by AI (91), (93) or (8005).'),
        };

        self::checkSerial($serial, $structure, self::CODE);
        [$key, $code, $extras] = match ($structure) {
            MarkingCodeStructure::Standard => self::standardTail($elements),
            MarkingCodeStructure::Shortened => [null, self::fixedLength($elements[2], self::KEY_LENGTH), \array_slice($elements, 3)],
            default => [null, self::tobaccoGroupCode($elements), []],
        };
        self::checkExtras($extras, $structure);

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
    private static function gs1Identifier(array $elements): RuMarkingParts
    {
        $count = \count($elements);
        $serial = self::serialAfterKey($elements);
        if (!$serial instanceof AiElement || $count > 3 || ($count === 3 && $elements[2]->spec->ai !== '8005')) {
            throw new InvalidMarkingCodeException(self::IDENTIFIER . ' must be AI (01) and AI (21), with AI (8005) for a tobacco group pack.');
        }

        $structure = $count === 3 ? MarkingCodeStructure::TobaccoGroupPack : null;
        self::checkSerial($serial, $structure, self::IDENTIFIER);
        $canonical = ElementStringSyntax::bracketedForm($elements);

        return new RuMarkingParts($structure, $canonical, $elements[0]->value, $serial->value, null, null, $canonical, ElementStringSyntax::barcodeData($elements));
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

    private static function checkSerial(AiElement $serial, ?MarkingCodeStructure $structure, string $type): void
    {
        $lengths = match ($structure) {
            MarkingCodeStructure::Standard => self::STANDARD_SERIALS,
            MarkingCodeStructure::TobaccoGroupPack => self::TOBACCO_GROUP_SERIALS,
            default => self::SHORTENED_SERIALS,
        };
        $length = \strlen($serial->value);
        if (!\in_array($length, $lengths, true)) {
            $where = $structure instanceof MarkingCodeStructure ? 'the ' . $structure->label() . ' structure has' : 'the published structures have';

            throw new InvalidMarkingCodeException(\sprintf('%s AI (21) has %d characters; %s %s.', $type, $length, $where, self::listLengths($lengths)));
        }
    }

    /**
     * @param non-empty-list<positive-int> $lengths
     */
    private static function listLengths(array $lengths): string
    {
        $last = \array_pop($lengths);

        return $lengths === [] ? (string) $last : \implode(', ', $lengths) . ' or ' . $last;
    }

    /**
     * @param non-empty-list<AiElement> $elements
     *
     * @return array{non-empty-string, non-empty-string, list<AiElement>}
     */
    private static function standardTail(array $elements): array
    {
        $key = self::fixedLength($elements[2], self::KEY_LENGTH);
        if (!isset($elements[3]) || $elements[3]->spec->ai !== '92') {
            throw new InvalidMarkingCodeException(self::CODE . ' AI (91) must be followed by AI (92).');
        }

        $length = \strlen($elements[3]->value);
        if (!\in_array($length, self::STANDARD_CODE_LENGTHS, true)) {
            throw new InvalidMarkingCodeException(\sprintf('%s AI (92) must have 44 or 88 characters, got %d.', self::CODE, $length));
        }

        return [$key, $elements[3]->value, \array_slice($elements, 4)];
    }

    /**
     * @param non-empty-list<AiElement> $elements
     *
     * @return non-empty-string
     */
    private static function tobaccoGroupCode(array $elements): string
    {
        if (!isset($elements[3]) || $elements[3]->spec->ai !== '93') {
            throw new InvalidMarkingCodeException(self::CODE . ' AI (8005) must be followed by AI (93).');
        }

        return self::fixedLength($elements[3], self::KEY_LENGTH);
    }

    /**
     * @return non-empty-string
     */
    private static function fixedLength(AiElement $element, int $length): string
    {
        if (\strlen($element->value) !== $length) {
            throw new InvalidMarkingCodeException(\sprintf('%s AI (%s) must have %d characters, got %d.', self::CODE, $element->spec->ai, $length, \strlen($element->value)));
        }

        return $element->value;
    }

    /**
     * The tobacco group pack takes "произвольное количество дополнительных групп данных" (Decree No. 224, p. 44);
     * the other structures only the one optional group their pages publish.
     *
     * @param list<AiElement> $extras
     */
    private static function checkExtras(array $extras, MarkingCodeStructure $structure): void
    {
        $allowed = match ($structure) {
            MarkingCodeStructure::Standard => self::STANDARD_EXTRAS,
            MarkingCodeStructure::Shortened => self::SHORTENED_EXTRAS,
            default => null,
        };
        if ($allowed === null || $extras === []) {
            return;
        }

        if (\count($extras) > 1 || !\in_array($extras[0]->spec->ai, $allowed, true)) {
            throw new InvalidMarkingCodeException(\sprintf('%s has a data group the %s structure does not publish after its verification code.', self::CODE, $structure->label()));
        }
    }
}
