<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Global Location Number: 13 digits, the last one the check digit. The GS1 Company Prefix length is a
 * registry fact, so the number is not split; {@see fromParts()} computes the check digit.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 7.9.1 (check digit)
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, the AI data format
 */
final class Gln extends AbstractStringValueObject implements IdentificationKey
{
    private const string TYPE = 'GLN';

    public static function fromParts(string $companyPrefix, string $reference): self
    {
        $body = KeyRules::body(self::TYPE, $companyPrefix, $reference, 12);

        return new self($body . Gs1CheckDigit::compute($body));
    }

    #[Override]
    public function gs1Prefix(): Gs1Prefix
    {
        return Gs1Prefix::fromDigits($this->value);
    }

    #[Override]
    protected function parse(string $value): string
    {
        KeyRules::checkedDigits($value, self::TYPE, 13);

        return $value;
    }
}
