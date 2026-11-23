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
 * The constructor (and `from()`) takes the canonical forms only: the 25 pack characters, or the bracketed element
 * string `(01)…(21)…`. 25 pack characters that also read as `01` + GTIN + `21` + 7 throw
 * {@see AmbiguousValueException}; {@see fromTobaccoConsumerPack()} reads them as the pack, and its value then keeps
 * the `]d1` prefix (no GS1 code carries it) so that it reads back unambiguously, as {@see MarkingCode} does. The
 * operator's document form `01…21…` ({@see toDocumentForm()}) is read by {@see fromDocumentForm()}, which throws
 * {@see AmbiguousValueException} when both readings are valid.
 *
 * @see https://markirovka.ru/knowledge/tovarnye-gruppy/upakovannaya-voda/sostav-koda-identifikatsii-dlya-ukazaniya-v-dokumentakh-v-razdele-markirovka-obuv «Состав кода идентификации для указания в документах»
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "Russian marking"
 */
final class MarkingIdentifier extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x: (01) + 14 digits, (21) + a 20-character serial whose every `(` is escaped as `\(`, (8005) + 6 digits. */
    public const int MAX_LENGTH = 74;

    /** Set by {@see parse()}; derived from the value, never serialized. */
    private ?RuMarkingParts $parts = null;

    /**
     * Also reads a payload of vo-gs1 0.x, which stored an ambiguous pack КИ as its bare 25 characters and read them as
     * the pack.
     *
     * @param array<array-key, mixed> $data
     */
    #[Override]
    public function __unserialize(array $data): void
    {
        try {
            parent::__unserialize($data);
        } catch (AmbiguousValueException) {
            $value = $data['value'] ?? null;
            \assert(\is_string($value));
            // The failed parse left the value unset, so the parent's fresh-instance guard lets the pack reading in.
            parent::__unserialize(['value' => RuMarkingSyntax::tobaccoConsumerPackIdentifier($value)->value]);
        }
    }

    /**
     * `01` + GTIN + `21` + serial (GS + `8005` + price for a tobacco group pack), or the 25 pack characters.
     *
     * @throws AmbiguousValueException when 25 characters read both ways
     */
    public static function fromDocumentForm(string $value): self
    {
        return new self(RuMarkingSyntax::documentForm($value)->value);
    }

    /**
     * The tobacco consumer-pack reading of 25 characters (optionally after `]d1`), never the GS1 one.
     */
    public static function fromTobaccoConsumerPack(string $value): self
    {
        return new self(RuMarkingSyntax::tobaccoConsumerPackIdentifier($value)->value);
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
        $this->parts = RuMarkingSyntax::identifier($value);

        return $this->parts->value;
    }

    private function parts(): RuMarkingParts
    {
        return $this->parts ??= RuMarkingSyntax::identifier($this->value);
    }
}
