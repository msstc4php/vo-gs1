<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The HIBC Basic UDI-DI: the flag `++`, the 4-character Labeler Identification Code (first character alphabetical), a
 * Global Model Identifier of 1 to 17 alphanumeric characters and two check characters, "Modulo 1021 arithmetic" over
 * every preceding character, `++` included, encoded in a 32-character set — the GS1 MOD 1021,32 scheme.
 *
 * @see https://www.hibcc.org/wp-content/uploads/HIBCC-Basic-UDI-DI.pdf HIBCC, HIBC Basic UDI-DI (example ++A999MODELIDENTIFIER11S8)
 * @see docs/sources/GS1.md "HIBCC"
 */
final class HibccBasicUdiDi extends AbstractStringValueObject implements BasicUdiDi
{
    /** Maximum characters of the stored value, frozen for 1.x: `++`, a 4-character LIC, at most 17 characters of model identifier and 2 check characters. */
    public const int MAX_LENGTH = 25;

    private const string PATTERN = '/^\+\+([A-Z][A-Z0-9]{3})([A-Za-z0-9]{1,17})([2-9A-HJ-NP-Z]{2})$/D';

    #[Override]
    public function issuingAgency(): IssuingAgency
    {
        return IssuingAgency::Hibcc;
    }

    /**
     * @return non-empty-string
     */
    public function labelerIdentificationCode(): string
    {
        return $this->parts()[1];
    }

    /**
     * @return non-empty-string
     */
    public function globalModelIdentifier(): string
    {
        return $this->parts()[2];
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidUdiException('HIBC Basic UDI-DI must be "++", a 4-character LIC starting with a letter, 1 to 17 alphanumeric characters and two check characters.');
        }

        if (GmnCheckCharacters::compute(\substr($value, 0, -2)) !== \substr($value, -2)) {
            throw new InvalidUdiException('HIBC Basic UDI-DI has wrong check characters.');
        }

        return $value;
    }

    /**
     * @return array{string, non-empty-string, non-empty-string, non-empty-string}
     */
    private function parts(): array
    {
        if (\preg_match(self::PATTERN, $this->value, $match) !== 1) {
            throw new LogicException('HIBC Basic UDI-DI lost its parts.');
        }

        return $match;
    }
}
