<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Global Coupon Number (AI 255): 13 digits (company prefix, coupon reference, check digit) and an optional serial of
 * up to 12 digits. The stored value is the AI 255 data.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 7.9.1 (check digit)
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, AI 255 (`N13,csum [N..12]`)
 */
final class Gcn extends AbstractStringValueObject implements IdentificationKey
{
    /** Maximum characters of the stored value, frozen for 1.x. */
    public const int MAX_LENGTH = 13 + self::SERIAL_MAX;

    private const string TYPE = 'GCN';

    private const int SERIAL_MAX = 12;

    public static function fromParts(string $companyPrefix, string $reference, ?string $serial = null): self
    {
        $body = KeyRules::body(self::TYPE, $companyPrefix, $reference, 12);

        return new self($body . Gs1CheckDigit::compute($body) . $serial);
    }

    /**
     * @return non-empty-string|null
     */
    public function serial(): ?string
    {
        $serial = \substr($this->value, 13);

        return $serial === '' ? null : $serial;
    }

    #[Override]
    public function gs1Prefix(): Gs1Prefix
    {
        return Gs1Prefix::fromDigits(\substr($this->value, 0, 13));
    }

    #[Override]
    protected function parse(string $value): string
    {
        KeyRules::checkedDigits(\substr($value, 0, 13), self::TYPE, 13);
        $serial = \substr($value, 13);
        if ($serial !== '') {
            if (\strlen($serial) > self::SERIAL_MAX) {
                throw KeyRules::fail('GCN serial', \sprintf('must have 1 to %d digits', self::SERIAL_MAX));
            }

            KeyRules::digits($serial, 'GCN serial', \strlen($serial));
        }

        return $value;
    }
}
