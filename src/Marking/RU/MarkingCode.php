<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Marking\RU;

use LogicException;
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
 * GS1-structured code in any notation {@see ElementString} accepts. A code whose GS separators were lost (copied
 * from a web form) is refused, never split at a guessed position.
 *
 * Canonical form: the pack code as is; a GS1-structured code as barcode data, AIs in the order given, GS (U+001D)
 * after each group that is not of predefined length except the last, as the operator issues it.
 *
 * @see https://markirovka.ru/knowledge/tovarnye-gruppy/ «Честный знак», «Состав кода маркировки» per product group
 * @see docs/sources/GS1.md "Russian marking"
 */
final class MarkingCode extends AbstractStringValueObject
{
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
     * AI (92) or (93), or the fourth group of the tobacco consumer-pack code.
     *
     * @return non-empty-string
     */
    public function verificationCode(): string
    {
        return $this->parts()->verificationCode ?? throw new LogicException('Russian marking code lost its verification code.');
    }

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
