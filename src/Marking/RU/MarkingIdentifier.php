<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Marking\RU;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\Internal\RuMarkingParts;
use MSSTC4PHP\ValueObject\Gs1\Internal\RuMarkingSyntax;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A Russian identification code («код идентификации», КИ): the marking code without its crypto tail, as documents
 * carry it. AI (01) and (21) for the GS1-structured codes; with AI (8005) for the tobacco group pack ("первые 3
 * группы образуют код идентификации групповой упаковки", Decree No. 224, p. 44); the first 25 characters of the
 * tobacco consumer-pack code (p. 43).
 *
 * Canonical form: the 25 pack characters, or the bracketed element string `(01)…(21)…`. The operator's document
 * form `01…21…` ({@see toDocumentForm()}) is accepted too, but 25 such characters can also read as a pack КИ: when
 * both readings are valid, {@see AmbiguousValueException} is thrown, so the canonical form stays bracketed.
 *
 * @see https://markirovka.ru/knowledge/tovarnye-gruppy/upakovannaya-voda/sostav-koda-identifikatsii-dlya-ukazaniya-v-dokumentakh-v-razdele-markirovka-obuv «Состав кода идентификации для указания в документах»
 * @see docs/sources/GS1.md "Russian marking"
 */
final class MarkingIdentifier extends AbstractStringValueObject
{
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

    public function isTobaccoConsumerPack(): bool
    {
        return $this->parts()->structure === MarkingCodeStructure::TobaccoConsumerPack;
    }

    /**
     * Unbracketed, with GS before AI (8005): `01` + GTIN + `21` + serial, as the operator's document pages show it.
     *
     * @return non-empty-string
     */
    public function toDocumentForm(): string
    {
        return $this->parts()->documentForm;
    }

    #[Override]
    protected function parse(string $value): string
    {
        return RuMarkingSyntax::identifier($value)->value;
    }

    private function parts(): RuMarkingParts
    {
        return RuMarkingSyntax::identifier($this->value);
    }
}
