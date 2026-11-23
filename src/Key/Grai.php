<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Global Returnable Asset Identifier (AI 8003): a zero, 13 digits (company prefix, asset type, check digit) and an
 * optional serial of up to 16 characters of CSET 82. The stored value is the AI 8003 data: {@see assetType()} is
 * its first 14 digits and {@see serial()} the rest.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 7.9.1 (check digit)
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, AI 8003 (`N1,zero N13,csum [X..16]`)
 */
final class Grai extends AbstractStringValueObject implements IdentificationKey
{
    /** Maximum characters of the stored value, frozen for 1.x. */
    public const int MAX_LENGTH = 14 + self::SERIAL_MAX;

    private const string TYPE = 'GRAI';

    private const int SERIAL_MAX = 16;

    public static function fromParts(string $companyPrefix, string $reference, ?string $serial = null): self
    {
        $body = KeyRules::body(self::TYPE, $companyPrefix, $reference, 12);

        return new self('0' . $body . Gs1CheckDigit::compute($body) . $serial);
    }

    /** The key without its serial: 14 digits starting with a zero. */
    /**
     * @return numeric-string
     */
    public function assetType(): string
    {
        $assetType = \substr($this->value, 0, 14);

        return \is_numeric($assetType) ? $assetType : throw new LogicException('GRAI lost its asset type.');
    }

    /**
     * @return non-empty-string|null
     */
    public function serial(): ?string
    {
        $serial = \substr($this->value, 14);

        return $serial === '' ? null : $serial;
    }

    #[Override]
    public function gs1Prefix(): Gs1Prefix
    {
        return Gs1Prefix::fromDigits(\substr($this->value, 1, 13));
    }

    #[Override]
    protected function parse(string $value): string
    {
        KeyRules::digits(\substr($value, 0, 14), self::TYPE, 14);
        if ($value[0] !== '0') {
            throw KeyRules::fail(self::TYPE, 'must start with a zero');
        }

        KeyRules::checkedDigits(\substr($value, 1, 13), self::TYPE, 13);

        if (\strlen($value) > 14) {
            KeyRules::text(\substr($value, 14), 'GRAI serial', Gs1Charsets::CSET_82, self::SERIAL_MAX);
        }

        return $value;
    }
}
