<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCodeStructure;

/**
 * @internal
 *
 * A parsed Russian marking code or identifier
 */
final readonly class RuMarkingParts
{
    /**
     * @param ?MarkingCodeStructure $structure null for an identifier of AI (01) and (21) only, which every
     *                                         GS1-structured code shares
     * @param non-empty-string $value the canonical form
     * @param non-empty-string $gtin 14 digits
     * @param non-empty-string $serial
     * @param ?non-empty-string $verificationKey
     * @param ?non-empty-string $verificationCode null for an identifier
     * @param non-empty-string $identifier the canonical form of the identifier (КИ)
     * @param non-empty-string $documentForm the identifier as the operator writes it in documents
     */
    public function __construct(
        public ?MarkingCodeStructure $structure,
        public string $value,
        public string $gtin,
        public string $serial,
        public ?string $verificationKey,
        public ?string $verificationCode,
        public string $identifier,
        public string $documentForm,
    ) {
    }
}
