<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

use LogicException;
use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * US National Drug Code in the current format of 21 CFR 207.33(b): labeler, product and package code in one of the
 * five permitted configurations ({@see NdcConfiguration}), written with hyphens (`0002-1433-80`). The hyphens are
 * required: bare digits fit several configurations ("there is no reliable way to know where the hyphens were", FDA,
 * 91 FR 10749), so a bare 10- or 11-digit number throws vo-base `AmbiguousValueException`; {@see candidates()} lists
 * its configurations and {@see fromDigits()} builds the NDC once the configuration is known.
 *
 * @see https://www.ecfr.gov/current/title-21/chapter-I/subchapter-C/part-207/subpart-B/section-207.33 21 CFR 207.33(b)
 * @see https://www.federalregister.gov/documents/2026/03/05/2026-04368/revising-the-national-drug-code-format-and-drug-label-barcode-requirements 91 FR 10749, Tables 1 and 2
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "FDA NDC"
 */
final class Ndc extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x. */
    public const int MAX_LENGTH = 13;

    private const string PATTERN = '/^(\d{4,6})-(\d{3,4})-(\d{1,2})$/D';

    /**
     * The configurations a bare digit string fits: three for 10 digits, two for 11, none otherwise.
     *
     * @return list<NdcConfiguration>
     */
    public static function candidates(string $digits): array
    {
        if (\strspn($digits, '0123456789') !== \strlen($digits)) {
            return [];
        }

        return \array_values(\array_filter(
            NdcConfiguration::cases(),
            static fn (NdcConfiguration $configuration): bool => $configuration->length() === \strlen($digits),
        ));
    }

    /**
     * Every 10-digit NDC whose {@see toBillingFormat()} is `$elevenDigits`, in the order of {@see NdcConfiguration}
     * (4-4-2, 5-3-2, 5-4-1); empty when there is none or the input is not 11 digits. The billing form displays "each
     * subcode using its largest valid size" (HHS, 65 FR 50312, p. 50329), so the padding zero sits at the start of the
     * one short segment, and every segment of the input that starts with `0` gives a candidate: `10010000101` is both
     * `10010-001-01` and `10010-0001-1`. More than one candidate is a genuine ambiguity, which only the source of the
     * value can settle.
     *
     * @see https://www.govinfo.gov/content/pkg/FR-2000-08-17/html/00-20820.htm 65 FR 50312, p. 50329
     *
     * @return list<self>
     */
    public static function candidatesFromBillingFormat(string $elevenDigits): array
    {
        if (\strlen($elevenDigits) !== 11 || \strspn($elevenDigits, '0123456789') !== 11) {
            return [];
        }

        $candidates = [];
        foreach (self::candidates(\substr($elevenDigits, 1)) as $configuration) {
            $labelerPadding = 5 - $configuration->labelerLength();
            $productPadding = 4 - $configuration->productLength();
            $labeler = \substr($elevenDigits, $labelerPadding, $configuration->labelerLength());
            $product = \substr($elevenDigits, 5 + $productPadding, $configuration->productLength());
            $package = \substr($elevenDigits, -$configuration->packageLength());
            $ndc = new self($labeler . '-' . $product . '-' . $package);
            if ($ndc->toBillingFormat() === $elevenDigits) {
                $candidates[] = $ndc;
            }
        }

        return $candidates;
    }

    public static function fromDigits(string $digits, NdcConfiguration $configuration): self
    {
        if (!\in_array($configuration, self::candidates($digits), true)) {
            throw new InvalidUdiException('NDC digits do not fit the given configuration.');
        }

        $product = \substr($digits, $configuration->labelerLength(), $configuration->productLength());

        return new self(\substr($digits, 0, $configuration->labelerLength()) . '-' . $product . '-' . \substr($digits, -$configuration->packageLength()));
    }

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

    public function configuration(): NdcConfiguration
    {
        [$labeler, $product, $package] = $this->segments();

        return NdcConfiguration::from(\sprintf('%d-%d-%d', \strlen($labeler), \strlen($product), \strlen($package)));
    }

    /**
     * The 11-digit HIPAA standard form (5-4-2, unhyphenated): "a leading zero is added to the appropriate segment"
     * (FDA, 91 FR 10749, Table 1: `10010-001-01` → `10010000101`). Null for a 6-digit labeler code: HIPAA defines no
     * conversion for it, and changing that is "outside the scope of FDA authority".
     *
     * @return numeric-string|null
     */
    public function toBillingFormat(): ?string
    {
        [$labeler, $product, $package] = $this->segments();
        if (\strlen($labeler) === 6) {
            return null;
        }

        $billing = \sprintf('%05d%04d%02d', $labeler, $product, $package);

        return \is_numeric($billing) ? $billing : throw new LogicException('NDC billing form must be digits.');
    }

    /**
     * The same NDC in the 12-digit format of the 2026 final rule: leading zeros added to each segment "as needed to
     * produce the 6-4-2 format" (91 FR 10749, Response 14).
     */
    public function toNdc12(): Ndc12
    {
        [$labeler, $product, $package] = $this->segments();

        return new Ndc12(\str_pad($labeler, 6, '0', STR_PAD_LEFT) . '-' . \str_pad($product, 4, '0', STR_PAD_LEFT) . '-' . \str_pad($package, 2, '0', STR_PAD_LEFT));
    }

    #[Override]
    protected function parse(string $value): string
    {
        if (\strlen($value) > self::MAX_LENGTH) {
            throw new InvalidUdiException(\sprintf('NDC must have at most %d characters.', self::MAX_LENGTH));
        }

        if (self::candidates($value) !== []) {
            throw new AmbiguousValueException('NDC configuration (bare digits; Ndc::candidates() lists the configurations they fit)', [self::class]);
        }

        if (\preg_match(self::PATTERN, $value, $match) !== 1) {
            throw new InvalidUdiException('NDC must be three hyphenated digit segments.');
        }

        if (NdcConfiguration::tryFrom(\sprintf('%d-%d-%d', \strlen($match[1]), \strlen($match[2]), \strlen($match[3]))) === null) {
            throw new InvalidUdiException('NDC segment lengths are not a configuration of 21 CFR 207.33(b)(1)(i) and (ii).');
        }

        return $value;
    }

    /**
     * @return array{numeric-string, numeric-string, numeric-string}
     */
    private function segments(): array
    {
        if (\preg_match(self::PATTERN, $this->value, $match) !== 1) {
            throw new LogicException('NDC value lost its segments.');
        }

        return [$match[1], $match[2], $match[3]];
    }
}
