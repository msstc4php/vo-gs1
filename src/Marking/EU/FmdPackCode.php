<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Marking\EU;

use DateTimeImmutable;
use LogicException;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Internal\IfaPackData;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The unique identifier of a medicinal product pack under Delegated Regulation (EU) 2016/161: product code, serial
 * number (at most 20 characters), batch and expiry date, plus national numbers where required.
 *
 * Two formats: a GS1 element string with AI (01), (21), (10) and (17), in any notation {@see ElementString} accepts;
 * or the IFA format, an ISO/IEC 15434 format 06 message (`[)>` RS `06` GS … RS EOT, the trailer optional on input)
 * with DI 9N (PPN), S, 1T and D.
 *
 * Canonical form: the GS1 barcode data with GS separators, or the IFA message with its trailer; data elements in the
 * order given. Both carry control characters (GS, RS, EOT) that storage must keep.
 *
 * @see https://www.legislation.gov.uk/eur/2016/161/article/4/adopted Delegated Regulation (EU) 2016/161, Article 4 (EUR-Lex unreachable, see GS1.md)
 * @see https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA_Spec_PPN_Code_Handelspackung_EN.pdf IFA, PPN-Code Specification for Retail Packaging, Version 3.06, chapter 5
 * @see docs/sources/GS1.md "EU: falsified medicines and tobacco"
 */
final class FmdPackCode extends AbstractStringValueObject
{
    /** Package bound; the four mandatory elements need at most 78 characters, the rest leaves room for NHRNs and a URL. */
    public const int MAX_LENGTH = 512;

    private const string TYPE = 'FMD pack code';

    /** Section 4.3: the GS1 Germany prefix of the NTIN-DE, made 14 digits. */
    private const string NTIN_DE_PREFIX = '04150';

    /** @var non-empty-list<non-empty-string> */
    private const array MANDATORY_AIS = ['01', '21', '10', '17'];

    public function isIfaFormat(): bool
    {
        return \str_starts_with($this->value, IfaPackData::HEADER);
    }

    public function productCode(): Gtin|Ppn
    {
        return $this->isIfaFormat() ? new Ppn($this->ifa()->get('9N')) : new Gtin($this->gs1Get('01'));
    }

    /**
     * @return non-empty-string
     */
    public function serial(): string
    {
        return $this->isIfaFormat() ? $this->ifa()->get('S') : $this->gs1Get('21');
    }

    /**
     * @return non-empty-string
     */
    public function batch(): string
    {
        return $this->isIfaFormat() ? $this->ifa()->get('1T') : $this->gs1Get('10');
    }

    /**
     * @return non-empty-string `YYMMDD`, `DD` may be `00`
     */
    public function expiry(): string
    {
        return $this->isIfaFormat() ? $this->ifa()->get('D') : $this->gs1Get('17');
    }

    /**
     * At 00:00:00 UTC, `DD = 00` as the last day of the month. The IFA format is 2000-2099 by IFA's rule; the GS1
     * format follows {@see ElementString::expiryDate()} and its `$today`.
     *
     * @throws InvalidElementStringException when the century chosen for `$today` has no such day (29 February)
     */
    public function expiryDate(?DateTimeImmutable $today = null): DateTimeImmutable
    {
        if ($this->isIfaFormat()) {
            return IfaPackData::expiryDate($this->ifa()->get('D'));
        }

        return $this->elementString()?->expiryDate($today) ?? throw new LogicException('FMD pack code lost its AI (17).');
    }

    /**
     * The German PZN when the product code carries it: a PPN with PRA Code 11 or an NTIN-DE (`04150` + PZN).
     *
     * @return ?non-empty-string 8 digits
     */
    public function pzn(): ?string
    {
        $productCode = $this->productCode();
        if ($productCode instanceof Ppn) {
            return $productCode->pzn();
        }

        $gtin14 = $productCode->toGtin14();
        if (!\str_starts_with($gtin14, self::NTIN_DE_PREFIX)) {
            return null;
        }

        $pzn = \substr($gtin14, \strlen(self::NTIN_DE_PREFIX), 8);

        return $pzn === '' ? null : $pzn;
    }

    /**
     * Null for the IFA format.
     */
    public function elementString(): ?ElementString
    {
        return $this->isIfaFormat() ? null : new ElementString($this->value);
    }

    #[Override]
    protected function parse(string $value): string
    {
        if ($value === '') {
            throw new InvalidMarkingCodeException(self::TYPE . ' is empty.');
        }

        if (\strlen($value) > self::MAX_LENGTH) {
            throw new InvalidMarkingCodeException(\sprintf('%s must have at most %d characters, got %d.', self::TYPE, self::MAX_LENGTH, \strlen($value)));
        }

        if (\str_starts_with($value, IfaPackData::HEADER)) {
            return IfaPackData::parse($value)->canonical();
        }

        try {
            $elementString = new ElementString($value);
        } catch (InvalidElementStringException $exception) {
            throw new InvalidMarkingCodeException(self::TYPE . ' (GS1) is not a valid element string: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
        }

        foreach (self::MANDATORY_AIS as $ai) {
            if (!$elementString->has($ai)) {
                throw new InvalidMarkingCodeException(self::TYPE . ' (GS1) must carry AI (01), (21), (10) and (17).');
            }
        }

        return $elementString->toBarcodeData();
    }

    private function ifa(): IfaPackData
    {
        return IfaPackData::parse($this->value);
    }

    /**
     * @return non-empty-string
     */
    private function gs1Get(string $ai): string
    {
        $data = $this->elementString()?->get($ai);
        if ($data === null || $data === '') {
            throw new LogicException(\sprintf('FMD pack code lost its AI (%s).', $ai));
        }

        return $data;
    }
}
