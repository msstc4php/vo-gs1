<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\DigitalLink;

use LogicException;
use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidDigitalLinkException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\DigitalLinkParts;
use MSSTC4PHP\ValueObject\Gs1\Internal\DigitalLinkSyntax;
use MSSTC4PHP\ValueObject\Gs1\Key\IdentificationKey;
use MSSTC4PHP\ValueObject\Network\Url;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * An uncompressed GS1 Digital Link URI: an http or https URL whose path ends in a GS1 primary key and its key
 * qualifiers, with GS1 data attributes in the query. Non-GS1 query parameters and the fragment are allowed and kept.
 *
 * The value is the URI as given: the standard defines no normal form short of the canonical URI ({@see toCanonical()}),
 * which drops the domain and the non-GS1 parameters. {@see equals()} compares what the standard treats as the same
 * link: scheme and host ignoring case, the same stem path, path data and non-GS1 parameters, and the same data
 * attributes in any order; percent-escapes compare after RFC 3986 section 6.2.2 normalisation (hex case, unreserved
 * characters). Two decisions beyond that: a port is compared as written (`:443` is not dropped), and non-GS1
 * parameters keep their order, as RFC 3986 gives the query no order-free meaning. A key qualifier given in the query
 * is read as the qualifier in the path. Compressed links are refused: URI Syntax 1.7.0 no longer defines compression.
 *
 * @see https://ref.gs1.org/standards/digital-link/uri-syntax/ GS1 Digital Link Standard: URI Syntax 1.7.0
 * @see docs/sources/GS1.md "GS1 Digital Link URI Syntax"
 */
final class DigitalLink extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x: a package bound against oversized input, not a GS1 rule; the value is the URI as given. */
    public const int MAX_LENGTH = DigitalLinkSyntax::MAX_LENGTH;

    /** The domain of the canonical URI (URI Syntax 1.7.0, section 4.12) and the default of {@see fromElementString()}. */
    public const string CANONICAL_DOMAIN = DigitalLinkSyntax::CANONICAL_STEM;

    /**
     * The URI of `$elementString` under `$domain` (scheme, host and an optional path stem): the first primary key AI
     * goes to the path with the longest qualifier sequence the data carries, every other AI to the query in the order
     * given.
     */
    public static function fromElementString(ElementString $elementString, string $domain = self::CANONICAL_DOMAIN): self
    {
        if (\strpbrk($domain, '?#') !== false || !Url::tryFrom($domain) instanceof Url) {
            throw new InvalidDigitalLinkException('GS1 Digital Link domain must be an http or https URL without query or fragment.');
        }

        $elements = [];
        foreach ($elementString->ais() as $ai) {
            $value = $elementString->get($ai);
            if ($value === null || $value === '') {
                throw new LogicException('Element string AI without data.');
            }

            $elements[] = new AiElement(AiTable::spec($ai) ?? throw new LogicException('Element string AI vanished from the table.'), $value);
        }

        [$path, $attributes] = DigitalLinkSyntax::arrange($elements);
        foreach ($attributes as $attribute) {
            if (!$attribute->spec->digitalLinkAttribute) {
                throw new InvalidDigitalLinkException('GS1 Digital Link cannot carry an AI of the element string: it is neither a key qualifier of the primary key nor a data attribute.');
            }
        }

        return new self(DigitalLinkSyntax::render($domain, $path, $attributes));
    }

    /**
     * The path data, then the data attributes in query order.
     */
    public function toElementString(): ElementString
    {
        return DigitalLinkSyntax::elementString($this->parts());
    }

    /**
     * The key of the path: for an ITIP (AI 8006) its GTIN.
     */
    public function primaryKey(): IdentificationKey
    {
        return DigitalLinkSyntax::keyFor($this->parts()->path[0]);
    }

    public function url(): Url
    {
        return Url::from($this->value);
    }

    /**
     * The canonical GS1 Digital Link URI of section 4.12: `https://id.gs1.org`, the GS1 path with a 14-digit GTIN,
     * the data attributes sorted by AI in lexical order, no other query parameter, no trailing slash, no fragment.
     */
    public function toCanonical(): self
    {
        $parts = $this->parts();

        return new self(DigitalLinkSyntax::render(DigitalLinkSyntax::CANONICAL_STEM, $parts->path, $this->sorted($parts->attributes)));
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->identity($this->parts()) === $this->identity($other->parts());
    }

    #[Override]
    protected function parse(string $value): string
    {
        DigitalLinkSyntax::elementString(DigitalLinkSyntax::parse($value));

        return $value;
    }

    private function parts(): DigitalLinkParts
    {
        return DigitalLinkSyntax::parse($this->value);
    }

    private function identity(DigitalLinkParts $parts): string
    {
        $escapes = DigitalLinkSyntax::normalisedEscapes(...);

        return \serialize([
            $parts->scheme,
            $escapes($parts->authority),
            $escapes($parts->stemPath),
            $parts->path,
            $this->sorted($parts->attributes),
            \array_map($escapes, $parts->extensions),
            $parts->fragment === null ? null : $escapes($parts->fragment),
        ]);
    }

    /**
     * @param list<AiElement> $attributes
     *
     * @return list<AiElement> in lexical order of the AI
     */
    private function sorted(array $attributes): array
    {
        \usort($attributes, static fn (AiElement $a, AiElement $b): int => \strcmp($a->spec->ai, $b->spec->ai));

        return $attributes;
    }
}
