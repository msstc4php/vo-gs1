<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

/**
 * @internal
 *
 * One GS1 Application Identifier as the GS1 Barcode Syntax Dictionary defines it. AI patterns in `requires` and
 * `excludes` use `n` for any digit (`310n`, `35nn`); an AI is never excluded by a pattern that matches itself.
 *
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, file header
 */
final readonly class AiSpec
{
    /**
     * @param non-empty-string $ai
     * @param non-empty-string|null $title the data title, null where the dictionary gives none
     * @param non-empty-list<AiComponent> $components
     * @param bool $requiresFnc1 false only for the predefined-length AIs (flag `*`)
     * @param list<non-empty-list<non-empty-list<non-empty-string>>> $requires each `req` attribute: one of its
     *                                                                         alternatives must be present, and an
     *                                                                         alternative lists AIs that must all be
     * @param list<non-empty-string> $excludes AIs and patterns that must not appear with this AI (`ex`)
     * @param bool $digitalLinkAttribute permitted as a GS1 Digital Link data attribute (flag `?`)
     * @param list<non-empty-list<non-empty-string>>|null $digitalLinkPrimaryKey null unless the AI is a Digital Link
     *                                                                           primary key; then its alternative
     *                                                                           ordered qualifier sequences
     */
    public function __construct(
        public string $ai,
        public ?string $title,
        public array $components,
        public bool $requiresFnc1,
        public array $requires,
        public array $excludes,
        public bool $digitalLinkAttribute,
        public ?array $digitalLinkPrimaryKey,
    ) {
    }
}
