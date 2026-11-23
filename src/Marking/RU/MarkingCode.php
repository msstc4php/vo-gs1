<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Marking\RU;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Internal\RuMarkingParts;
use MSSTC4PHP\ValueObject\Gs1\Internal\RuMarkingSyntax;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A Russian marking code («код маркировки», КМ): the identification code (КИ) with its crypto tail, in one of the
 * structures of {@see MarkingCodeStructure}. Only the format of the crypto tail is checked: verifying it needs the
 * operator's keys.
 *
 * Input: the tobacco consumer-pack code (29 characters, optionally after `]d1`, `]d2` or a leading GS), or a
 * GS1-structured code in any notation {@see ElementString} accepts. Data is never split at a guessed position. A
 * code whose GS separators were lost (copied from a web form) is refused when its last group has an unpublished length
 * and the rest after a published length parses as the AI a published structure puts next, or when no (92)/(93) is
 * left; brackets and GS separators prove their boundaries. With intact separators, a code that fits no published
 * structure is {@see MarkingCodeStructure::GenericGs1}.
 *
 * 29 GS-free characters that are both a pack code and `01` + GTIN + `21` + 5 + `93` + 4 throw
 * {@see AmbiguousValueException}; {@see fromTobaccoConsumerPack()} (or `]d1` input, which no GS1 code has) reads the
 * pack, whose canonical value then keeps the `]d1` prefix so that it reads back unambiguously.
 *
 * Residual, undetectable without the separators: a GenericGs1 code (unpublished serial length) whose GS after (21)
 * was lost can have its next AIs swallowed into the serial and be accepted as a different code.
 *
 * Canonical form: the pack code as is (with `]d1` in the case above); a GS1-structured code as barcode data, AIs in the order given, GS (U+001D)
 * after each group that is not of predefined length except the last, as the operator issues it.
 *
 * @see https://markirovka.ru/knowledge/tovarnye-gruppy/ «Честный знак», «Состав кода маркировки» per product group
 * @see docs/sources/GS1.md "Russian marking"
 */
final class MarkingCode extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x: a package bound; the stored form is never longer than the input. */
    public const int MAX_LENGTH = RuMarkingSyntax::MAX_LENGTH;

    /**
     * The tobacco consumer-pack reading of 29 characters (optionally after `]d1`, `]d2` or a GS), never the GS1 one.
     */
    public static function fromTobaccoConsumerPack(string $value): self
    {
        return new self(RuMarkingSyntax::tobaccoConsumerPack($value)->value);
    }

    public function structure(): MarkingCodeStructure
    {
        return $this->parts()->structure ?? throw new LogicException('Russian marking code lost its structure.');
    }

    public function gtin(): Gtin
    {
        return new Gtin($this->parts()->gtin);
    }

    /**
     * @return non-empty-string
     */
    public function serial(): string
    {
        return $this->parts()->serial;
    }

    /**
     * AI (91), the verification key identifier of the standard structure.
     *
     * @return ?non-empty-string
     */
    public function verificationKey(): ?string
    {
        return $this->parts()->verificationKey;
    }

    /**
     * AI (92) or (93), or the fourth group of the tobacco consumer-pack code; never null, every structure has one.
     *
     * @return non-empty-string
     */
    public function verificationCode(): string
    {
        return $this->parts()->verificationCode ?? throw new LogicException('Russian marking code lost its verification code.');
    }

    /**
     * Built from this code's own parts in a canonical form, so it never throws.
     */
    public function identifier(): MarkingIdentifier
    {
        return new MarkingIdentifier($this->parts()->identifier);
    }

    /**
     * Null for the tobacco consumer-pack code, which has no AIs.
     */
    public function elementString(): ?ElementString
    {
        return $this->structure() === MarkingCodeStructure::TobaccoConsumerPack ? null : new ElementString($this->value);
    }

    #[Override]
    protected function parse(string $value): string
    {
        return RuMarkingSyntax::code($value)->value;
    }

    private function parts(): RuMarkingParts
    {
        return RuMarkingSyntax::code($this->value);
    }
}
