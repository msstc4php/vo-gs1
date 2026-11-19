<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Global Model Number (AI 8013), the Basic UDI-DI of GS1: the GS1 Company Prefix and the model reference (up to 23
 * characters of CSET 82) followed by two check characters (MOD 1021,32).
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 1.2.3.3 (GS1 Company Prefix, four to twelve digits) and Table 7-20 (CSET 82)
 * @see https://github.com/gs1/gs1-syntax-dictionary `src/lint_gcppos1.c` (four leading digits), tag 2026-01-27
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, sections 7.9.5 and 3.9.13
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, AI 8013 (`X..25,csumalpha`)
 */
final class Gmn extends AbstractStringValueObject implements IdentificationKey
{
    private const string TYPE = 'GMN';

    private const int MAX = 25;

    public static function fromParts(string $companyPrefix, string $reference): self
    {
        $body = KeyRules::textBody(self::TYPE, $companyPrefix, $reference);
        KeyRules::text($body, 'GMN company prefix and model reference', Gs1Charsets::CSET_82, self::MAX - 2);

        return new self($body . GmnCheckCharacters::compute($body));
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
        if (GmnCheckCharacters::compute(\substr($value, 0, -2)) !== \substr($value, -2)) {
            throw KeyRules::fail(self::TYPE, 'has wrong check characters');
        }

        return $value;
    }
}
