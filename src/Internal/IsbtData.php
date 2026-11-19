<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use DateTimeImmutable;

/**
 * @internal
 *
 * The data structures of an ISBT 128 UDI
 */
final readonly class IsbtData
{
    /**
     * @param non-empty-list<non-empty-string> $structures every data structure with its data identifier, in order
     * @param non-empty-string $deviceIdentifier the 16 characters of the Processor Product Identification Code
     * @param non-empty-string $facilityIdentificationNumber
     * @param non-empty-string $facilityProductCode
     * @param non-empty-string $productDescriptionCode
     * @param non-empty-string $donationIdentificationNumber the 13 characters without the flag characters
     * @param non-empty-string $productDivisions
     * @param non-empty-string|null $lot
     */
    public function __construct(
        public array $structures,
        public string $deviceIdentifier,
        public string $facilityIdentificationNumber,
        public string $facilityProductCode,
        public string $productDescriptionCode,
        public string $donationIdentificationNumber,
        public string $productDivisions,
        public ?DateTimeImmutable $expiryDate,
        public ?DateTimeImmutable $productionDate,
        public ?string $lot,
    ) {
    }
}
