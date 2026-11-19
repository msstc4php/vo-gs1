<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

/**
 * @internal
 *
 * One AI with its data, as it appears in an element string
 */
final readonly class AiElement
{
    /**
     * @param non-empty-string $value the data, unescaped
     */
    public function __construct(
        public AiSpec $spec,
        public string $value,
    ) {
    }
}
