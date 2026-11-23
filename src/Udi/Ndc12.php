<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * US National Drug Code in the 12-digit 6-4-2 format of the FDA final rule of 2026-03-05 (91 FR 10749), effective
 * 2033-03-07: "the NDC must consist of 12 digits" — a 6-digit labeler code, a 4-digit product code and a 2-digit
 * package code. Accepted with hyphens or as 12 bare digits (one configuration, so no ambiguity); stored hyphenated.
 *
 * @see https://www.federalregister.gov/documents/2026/03/05/2026-04368/revising-the-national-drug-code-format-and-drug-label-barcode-requirements 91 FR 10749, amended 21 CFR 207.33(b)(1)
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "FDA NDC"
 */
final class Ndc12 extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x: 12 digits and two hyphens. */
    public const int MAX_LENGTH = 14;

    private const string PATTERN = '/^(\d{6})-?(\d{4})-?(\d{2})$/D';

    /**
     * @return numeric-string
     */
    public function labelerCode(): string
    {
        return $this->segments()[0];
    }

    /**
     * @return numeric-string
     */
    public function productCode(): string
    {
        return $this->segments()[1];
    }

    /**
     * @return numeric-string
     */
    public function packageCode(): string
    {
        return $this->segments()[2];
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (\preg_match(self::PATTERN, $value, $match) !== 1 || \substr_count($value, '-') === 1) {
            throw new InvalidUdiException('NDC12 must be 12 digits, bare or hyphenated 6-4-2.');
        }

        return $match[1] . '-' . $match[2] . '-' . $match[3];
    }

    /**
     * @return array{numeric-string, numeric-string, numeric-string}
     */
    private function segments(): array
    {
        if (\preg_match(self::PATTERN, $this->value, $match) !== 1) {
            throw new LogicException('NDC12 value lost its segments.');
        }

        return [$match[1], $match[2], $match[3]];
    }
}
