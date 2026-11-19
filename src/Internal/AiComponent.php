<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

/**
 * @internal
 *
 * One component of an AI data field: `N14,csum` is numeric, 14 to 14 characters, linted by `csum`. Only the last
 * component of a field may vary in length; an optional component (`[N3]`) is never followed by a mandatory one.
 */
final readonly class AiComponent
{
    /**
     * @param positive-int $minLength
     * @param positive-int $maxLength
     * @param list<non-empty-string> $linters names of the GS1 Barcode Syntax Tests to run on this component
     */
    public function __construct(
        public AiCharset $charset,
        public int $minLength,
        public int $maxLength,
        public bool $optional,
        public array $linters,
    ) {
    }
}
