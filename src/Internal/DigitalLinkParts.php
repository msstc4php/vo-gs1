<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

/**
 * @internal
 *
 * A GS1 Digital Link URI taken apart. Data values are percent-decoded, a zero-suppressed GTIN is padded to 14 digits;
 * everything else is kept as given.
 */
final readonly class DigitalLinkParts
{
    /**
     * @param string $scheme "http" or "https"
     * @param string $authority userinfo as given (case-sensitive, RFC 3986), host and port in lower case
     * @param string $stemPath the path before the GS1 path, empty or starting with "/"
     * @param non-empty-list<AiElement> $path the primary key, then its qualifiers
     * @param list<AiElement> $attributes the GS1 data attributes of the query, in order
     * @param list<string> $extensions every other query parameter, undecoded, in order
     * @param string|null $fragment with its "#", null without one
     */
    public function __construct(
        public string $scheme,
        public string $authority,
        public string $stemPath,
        public array $path,
        public array $attributes,
        public array $extensions,
        public ?string $fragment,
    ) {
    }
}
