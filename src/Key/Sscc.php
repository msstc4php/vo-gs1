<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Serial Shipping Container Code (AI 00): 18 digits, an extension digit, the GS1 Company Prefix and serial
 * reference (16 digits together) and the check digit. The company prefix length is a registry fact, so only the
 * extension digit is split off.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 7.9.1 (check digit)
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, AI 00
 */
final class Sscc extends AbstractStringValueObject implements IdentificationKey
{
    private const string TYPE = 'SSCC';

    /**
     * @param string $extensionDigit one digit, placed before the company prefix
     */
    public static function fromParts(string $companyPrefix, string $reference, string $extensionDigit = '0'): self
    {
        KeyRules::digits($extensionDigit, 'SSCC extension digit', 1);
        $body = $extensionDigit . KeyRules::body(self::TYPE, $companyPrefix, $reference, 16);

        return new self($body . Gs1CheckDigit::compute($body));
    }

    public function extensionDigit(): string
    {
        return $this->value[0];
    }

    #[Override]
    public function gs1Prefix(): Gs1Prefix
    {
        return Gs1Prefix::fromDigits(\substr($this->value, 1));
    }

    #[Override]
    protected function parse(string $value): string
    {
        KeyRules::checkedDigits($value, self::TYPE, 18);

        return $value;
    }
}
