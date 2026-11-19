<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcDateFormat;

/**
 * @internal
 *
 * The parts of an HIBC LIC data string
 */
final readonly class HibcData
{
    /**
     * @param non-empty-string $primary the primary data structure without its check character
     * @param non-empty-string $labelerIdentificationCode
     * @param non-empty-string $productNumber
     * @param int<0, 9> $unitOfMeasure
     * @param non-empty-string|null $lot
     * @param non-empty-string|null $serial
     * @param int<0, 99999>|null $quantity
     * @param non-empty-string|null $supplementalSerial the serial number of the supplemental data identifier S
     */
    public function __construct(
        public string $primary,
        public string $labelerIdentificationCode,
        public string $productNumber,
        public int $unitOfMeasure,
        public ?string $lot = null,
        public ?string $serial = null,
        public ?DateTimeImmutable $expiryDate = null,
        public ?HibcDateFormat $expiryDateFormat = null,
        public ?DateTimeImmutable $manufactureDate = null,
        public ?int $quantity = null,
        public ?string $supplementalSerial = null,
    ) {
    }
}
