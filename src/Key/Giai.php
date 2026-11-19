<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Global Individual Asset Identifier (AI 8004): the GS1 Company Prefix followed by the individual asset reference, up to 30 characters of CSET 82.
 * It has no check digit, and its first digits are the company prefix, so {@see gs1Prefix()} reads them.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 1.2.3.3 (GS1 Company Prefix, four to twelve digits) and Table 7-20 (CSET 82)
 * @see https://github.com/gs1/gs1-syntax-dictionary `src/lint_gcppos1.c` (four leading digits), tag 2026-01-27
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, AI 8004 (`X..30`)
 */
final class Giai extends AbstractStringValueObject implements IdentificationKey
{
    private const string TYPE = 'GIAI';

    private const int MAX = 30;

    public static function fromParts(string $companyPrefix, string $reference): self
    {
        return new self(KeyRules::textBody(self::TYPE, $companyPrefix, $reference));
    }

    #[Override]
    public function gs1Prefix(): Gs1Prefix
    {
        return Gs1Prefix::fromDigits(KeyRules::leadingDigits($this->value));
    }

    #[Override]
    protected function parse(string $value): string
    {
        KeyRules::text($value, self::TYPE, Gs1Charsets::CSET_82, self::MAX);
        KeyRules::startsWithPrefixDigits($value, self::TYPE);

        return $value;
    }
}
