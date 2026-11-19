<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Global Document Type Identifier (AI 253): 13 digits (company prefix, document type, check digit) and an optional
 * serial of up to 17 characters of CSET 82. The stored value is the AI 253 data: {@see documentType()} is its first
 * 13 digits and {@see serial()} the rest.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 7.9.1 (check digit)
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, AI 253 (`N13,csum [X..17]`)
 */
final class Gdti extends AbstractStringValueObject implements IdentificationKey
{
    private const string TYPE = 'GDTI';

    private const int SERIAL_MAX = 17;

    public static function fromParts(string $companyPrefix, string $reference, ?string $serial = null): self
    {
        $body = KeyRules::body(self::TYPE, $companyPrefix, $reference, 12);

        return new self($body . Gs1CheckDigit::compute($body) . $serial);
    }

    /** The key without its serial: 13 digits. */
    public function documentType(): string
    {
        return \substr($this->value, 0, 13);
    }

    public function serial(): ?string
    {
        return \strlen($this->value) > 13 ? \substr($this->value, 13) : null;
    }

    #[Override]
    public function gs1Prefix(): Gs1Prefix
    {
        return Gs1Prefix::fromDigits($this->documentType());
    }

    #[Override]
    protected function parse(string $value): string
    {
        KeyRules::checkedDigits(\substr($value, 0, 13), self::TYPE, 13);
        if (\strlen($value) > 13) {
            KeyRules::text(\substr($value, 13), 'GDTI serial', Gs1Charsets::CSET_82, self::SERIAL_MAX);
        }

        return $value;
    }
}
