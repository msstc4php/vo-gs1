<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\ElementString;

/**
 * One `req` attribute of the GS1 Barcode Syntax Dictionary that an element string does not satisfy: AI `$ai` needs
 * one of `$alternatives`, each a group of AIs (or patterns, `n` = any digit) that must all be present.
 *
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, `req` in the file header
 */
final readonly class UnmetRequirement
{
    /**
     * @internal built by {@see ElementString::unmetRequirements()}; the constructor is outside the BC promise
     *
     * @param non-empty-string $ai
     * @param non-empty-list<non-empty-list<non-empty-string>> $alternatives
     */
    public function __construct(
        public string $ai,
        public array $alternatives,
    ) {
    }

    /**
     * `(01) or (02)`, `(01)+(21) or (253)`.
     *
     * @return non-empty-string
     */
    public function describe(): string
    {
        $names = [];
        foreach ($this->alternatives as $group) {
            $names[] = '(' . \implode(')+(', $group) . ')';
        }

        return \implode(' or ', $names);
    }
}
