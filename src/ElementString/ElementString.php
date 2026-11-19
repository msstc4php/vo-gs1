<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\ElementString;

use DateTimeImmutable;
use DateTimeZone;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Key\Sscc;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * A GS1 element string: one or more AIs with their data, checked against the GS1 Barcode Syntax Dictionary (data
 * format, linters, invalid and mandatory AI pairs).
 *
 * Accepted notations: bracketed `(01)09506000134352(10)ABC`, with a literal `(` in data written `\(`; unbracketed with
 * FNC1 as GS (U+001D) or `^`, optionally after a symbology identifier (`]C1`, `]e0`, `]d2`, `]Q3`, `]J1`) or one
 * leading FNC1. The canonical value is the bracketed form, AIs in the order given: two notations of the same data are
 * equal, the same AIs in another order are not.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, sections 1.4.2, 3, 7.8, 7.12
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary
 * @see docs/sources/GS1.md "Element strings"
 */
final class ElementString extends AbstractStringValueObject
{
    /** @var non-empty-list<AiElement> */
    private array $elements;

    /**
     * The data of the first occurrence of `$ai`, unescaped.
     */
    public function get(string $ai): ?string
    {
        foreach ($this->elements as $element) {
            if ($element->spec->ai === $ai) {
                return $element->value;
            }
        }

        return null;
    }

    public function has(string $ai): bool
    {
        return $this->get($ai) !== null;
    }

    /**
     * @return non-empty-list<non-empty-string> each AI once, in the order of first appearance
     */
    public function ais(): array
    {
        $ais = [$this->elements[0]->spec->ai];
        foreach ($this->elements as $element) {
            if (!\in_array($element->spec->ai, $ais, true)) {
                $ais[] = $element->spec->ai;
            }
        }

        return $ais;
    }

    /**
     * AI (01), as 14 digits.
     */
    public function gtin(): ?Gtin
    {
        $value = $this->get('01');

        return $value === null ? null : new Gtin($value);
    }

    /**
     * AI (00).
     */
    public function sscc(): ?Sscc
    {
        $value = $this->get('00');

        return $value === null ? null : new Sscc($value);
    }

    /**
     * AI (10).
     */
    public function batch(): ?string
    {
        return $this->get('10');
    }

    /**
     * AI (21).
     */
    public function serial(): ?string
    {
        return $this->get('21');
    }

    /**
     * AI (17), at 00:00:00 UTC; the century follows GS1's sliding window around the UTC year of `$today` (default:
     * now), `DD = 00` is the last day of the month.
     *
     * @throws InvalidElementStringException when the century chosen for `$today` has no such day (29 February)
     */
    public function expiryDate(?DateTimeImmutable $today = null): ?DateTimeImmutable
    {
        return $this->date('17', $today);
    }

    /**
     * AI (11), read as {@see expiryDate()}.
     *
     * @throws InvalidElementStringException when the century chosen for `$today` has no such day (29 February)
     */
    public function productionDate(?DateTimeImmutable $today = null): ?DateTimeImmutable
    {
        return $this->date('11', $today);
    }

    /**
     * The unbracketed data a barcode carries: GS (U+001D) after each AI that is not of predefined length, except the
     * last; no symbology identifier and no leading FNC1.
     *
     * @return non-empty-string
     */
    public function toBarcodeData(): string
    {
        return ElementStringSyntax::barcodeData($this->elements);
    }

    #[Override]
    protected function parse(string $value): string
    {
        $this->elements = ElementStringSyntax::parse($value);

        return ElementStringSyntax::bracketedForm($this->elements);
    }

    private function date(string $ai, ?DateTimeImmutable $today): ?DateTimeImmutable
    {
        $value = $this->get($ai);
        if ($value === null) {
            return null;
        }

        return Gs1Dates::toDate($value, $today ?? new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ?? throw new InvalidElementStringException(\sprintf('AI (%s) is not a calendar date in the century chosen for the given day.', $ai));
    }
}
