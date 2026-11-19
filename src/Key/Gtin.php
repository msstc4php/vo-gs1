<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

use MSSTC4PHP\ValueObject\Contract\ValueObject;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * Global Trade Item Number (AI 01): 8, 12, 13 or 14 digits, the last one the check digit. The value keeps the form
 * given (EAN-8, UPC-A, EAN-13 or GTIN-14), because the leading zeros of a GTIN-12 are part of its U.P.C. Company
 * Prefix. Leading zeros do not change the GTIN, so {@see equals()} compares the 14-digit form: `4006381333931`
 * equals `04006381333931`.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, sections 1.3.1, 2.1.1.10 and 7.9.1
 * @see docs/sources/GS1.md "GTIN formats and the 14-digit form" and "GS1 Prefix list"
 */
final class Gtin extends AbstractStringValueObject implements IdentificationKey
{
    private const string TYPE = 'GTIN';

    /**
     * Builds a GTIN-13 or GTIN-12 from the GS1 Company Prefix (U.P.C. Company Prefix for a GTIN-12, with its
     * leading zero) and the item reference, or a GTIN-8 from the GS1-8 Prefix and the item reference. A GTIN-14 has
     * its indicator digit before the prefix: pass the 14 digits to the constructor instead.
     */
    public static function fromParts(string $companyPrefix, string $reference): self
    {
        $minPrefix = \strlen($companyPrefix . $reference) === 7 ? 2 : 4;
        $body = KeyRules::body(self::TYPE, $companyPrefix, $reference, [7, 11, 12], $minPrefix);

        return new self($body . Gs1CheckDigit::compute($body));
    }

    public function format(): GtinFormat
    {
        return GtinFormat::from(\strlen($this->value));
    }

    /**
     * @return non-empty-string
     */
    public function toGtin14(): string
    {
        return \str_pad($this->value, 14, '0', STR_PAD_LEFT);
    }

    /**
     * @return numeric-string|null
     */
    public function asGtin13(): ?string
    {
        return $this->shortened(13);
    }

    /**
     * @return numeric-string|null
     */
    public function asGtin12(): ?string
    {
        return $this->shortened(12);
    }

    /**
     * @return numeric-string|null
     */
    public function asGtin8(): ?string
    {
        return $this->shortened(8);
    }

    #[Override]
    public function gs1Prefix(): Gs1Prefix
    {
        $gtin14 = $this->toGtin14();

        return \str_starts_with(\substr($gtin14, 1), '00000')
            ? Gs1Prefix::fromGtin8Digits(\substr($gtin14, 6))
            : Gs1Prefix::fromDigits(\substr($gtin14, 1));
    }

    #[Override]
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->toGtin14() === $this->toGtin14();
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (!\in_array(\strlen($value), [8, 12, 13, 14], true)) {
            throw KeyRules::fail(self::TYPE, \sprintf('must have 8, 12, 13 or 14 digits, got %d characters', \strlen($value)));
        }

        KeyRules::checkedDigits($value, self::TYPE, \strlen($value));

        return $value;
    }

    /**
     * @param 8|12|13 $length
     *
     * @return numeric-string|null
     */
    private function shortened(int $length): ?string
    {
        $padded = $this->toGtin14();
        $zeros = 14 - $length;
        $short = \substr($padded, $zeros);

        return \strspn($padded, '0') >= $zeros && \is_numeric($short) ? $short : null;
    }
}
