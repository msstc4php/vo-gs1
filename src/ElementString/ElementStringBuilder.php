<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\ElementString;

use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;

/**
 * Builds an {@see ElementString} from AIs and their unescaped data, in the order added. Immutable: `with()` returns a
 * new builder. The AI is checked against the table at once; the data and the AI pairs on {@see build()}.
 */
final class ElementStringBuilder
{
    /** @var list<array{non-empty-string, string}> AI and unescaped data, only ever appended by {@see with()} */
    private array $elements = [];

    public function with(string $ai, string $value): self
    {
        $spec = ElementStringSyntax::spec($ai);
        // A backslash is in no GS1 character set; refused here, it would turn the next "(" into data.
        if (\str_contains($value, '\\')) {
            throw ElementStringSyntax::fail(\sprintf('Element string AI (%s) data contains a character outside its GS1 character set.', $spec->ai));
        }

        $builder = clone $this;
        $builder->elements[] = [$spec->ai, $value];

        return $builder;
    }

    public function build(): ElementString
    {
        $bracketed = '';
        foreach ($this->elements as [$ai, $value]) {
            $bracketed .= '(' . $ai . ')' . ElementStringSyntax::escape($value);
        }

        return new ElementString($bracketed);
    }
}
