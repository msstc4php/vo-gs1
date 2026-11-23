<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Epc;

use MSSTC4PHP\ValueObject\Gs1\Internal\EpcSyntax;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * An EPC Tag URI, `urn:epc:tag:[control fields:]<encoding>:<filter>.<components>`: the pure identity URI of its
 * scheme plus the binary encoding, the filter value and optional control fields (`[att=xNN]`, `[umi=B]`,
 * `[xpc=xNNNN]`, in that order). The filter must fit its field (any value, reserved or not, is accepted), the GS1
 * Company Prefix length must have a row in the encoding's partition table, and the fields must fit the encoding
 * (Table 12-2). The binary and hex forms are out of scope.
 *
 * @see https://ref.gs1.org/standards/tds/ EPC Tag Data Standard 2.3, sections 10, 12 and 14.6
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "EPC Tag Data Standard"
 */
final class EpcTagUri extends AbstractStringValueObject
{
    /** Maximum characters of the stored value, frozen for 1.x: a package bound against oversized input. */
    public const int MAX_LENGTH = EpcSyntax::MAX_LENGTH;

    /**
     * The tag URI of `$uri` in `$encoding` (TDS 12.3.2), without control fields.
     *
     * @param int<0, max>|null $filter
     */
    public static function fromPureIdentityUri(EpcPureIdentityUri $uri, EpcTagEncoding $encoding, ?int $filter): self
    {
        $scheme = $uri->scheme();
        if ($encoding->scheme() !== $scheme) {
            throw EpcSyntax::fail('EPC Tag URI encoding is not one of the scheme of the pure identity URI.');
        }

        if ($encoding->hasFilter() !== ($filter !== null)) {
            throw EpcSyntax::fail($filter === null ? 'EPC Tag URI encoding needs a filter value.' : 'EPC Tag URI encoding has no filter value.');
        }

        return new self('urn:epc:tag:' . $encoding->value . ':' . ($filter === null ? '' : $filter . '.') . EpcSyntax::pure($uri->value)[1]);
    }

    public function scheme(): EpcScheme
    {
        return $this->encoding()->scheme();
    }

    public function encoding(): EpcTagEncoding
    {
        return EpcSyntax::tag($this->value)[0];
    }

    /**
     * Null for GID-96, which has no filter.
     *
     * @return int<0, 63>|null
     */
    public function filter(): ?int
    {
        return EpcSyntax::tag($this->value)[1];
    }

    /**
     * The partition value: 12 minus the digits of the GS1 Company Prefix (0 to 6); null for the encodings without a
     * partition table (GID, USDOD, ADI).
     *
     * @return int<0, 6>|null
     */
    public function partition(): ?int
    {
        [$encoding, , $body] = EpcSyntax::tag($this->value);
        if (!$encoding->isPartitioned()) {
            return null;
        }

        $partition = 12 - \strlen(EpcSyntax::components($encoding->scheme(), $body)[0]);
        \assert($partition >= 0 && $partition <= 6, 'The company prefix has 6 to 12 digits.');

        return $partition;
    }

    /**
     * The URI without encoding, filter and control fields (TDS 12.3.3).
     */
    public function toPureIdentityUri(): EpcPureIdentityUri
    {
        [$encoding, , $body] = EpcSyntax::tag($this->value);

        return new EpcPureIdentityUri('urn:epc:id:' . $encoding->scheme()->value . ':' . $body);
    }

    #[Override]
    protected function parse(string $value): string
    {
        $canonical = EpcSyntax::canonical($value);
        [$encoding, , $body] = EpcSyntax::tag($canonical);
        $pure = new EpcPureIdentityUri('urn:epc:id:' . $encoding->scheme()->value . ':' . $body);
        EpcSyntax::checkEncoding($encoding, EpcSyntax::components($pure->scheme(), $body));

        return $canonical;
    }
}
