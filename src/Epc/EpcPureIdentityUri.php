<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Epc;

use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Internal\EpcSyntax;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * An EPC pure identity URI, `urn:epc:id:<scheme>:<components>`, for every scheme of EPC Tag Data Standard 2.3. Each
 * scheme's grammar is checked, the GS1 Company Prefix component has 6 to 12 digits (section 7.1), and the schemes
 * that correspond to GS1 keys must give a valid GS1 element string (section 7).
 *
 * The canonical value is the URI with the hex digits of percent escapes in upper case: TDS compares EPC URIs as
 * strings after that normalisation only (section 6.1), so `A` and `%41` stay different.
 *
 * @see https://ref.gs1.org/standards/tds/ EPC Tag Data Standard 2.3, sections 5, 6 and 7
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "EPC Tag Data Standard"
 */
final class EpcPureIdentityUri extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x: a package bound against oversized input. */
    public const int MAX_LENGTH = EpcSyntax::MAX_LENGTH;

    /**
     * The URI of `$scheme` for the GS1 data in `$elementString`. The length of the GS1 Company Prefix is a registry
     * fact, so the caller gives it: 6 for a 4- or 5-digit prefix (TDS 7.1), 8 for a GTIN-8 (7.3.2), 12 for an
     * individually assigned key (7.2).
     *
     * @param int<6, 12> $companyPrefixLength
     */
    public static function fromElementString(ElementString $elementString, EpcScheme $scheme, int $companyPrefixLength): self
    {
        return new self('urn:epc:id:' . $scheme->value . ':' . EpcSyntax::body($elementString, $scheme, $companyPrefixLength));
    }

    public function scheme(): EpcScheme
    {
        return EpcSyntax::pure($this->value)[0];
    }

    /**
     * The element string of TDS section 7, or null for the schemes that have none (GID, USDOD, ADI, BIC, IMOVN).
     */
    public function toElementString(): ?ElementString
    {
        [$scheme, $body] = EpcSyntax::pure($this->value);

        return EpcSyntax::elementString($scheme, EpcSyntax::components($scheme, $body));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $canonical = EpcSyntax::canonical($value);
        [$scheme, $body] = EpcSyntax::pure($canonical);
        EpcSyntax::elementString($scheme, EpcSyntax::components($scheme, $body));

        return $canonical;
    }
}
