<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Key\Gmn;
use MSSTC4PHP\ValueObject\Gs1\Udi\IssuingAgency;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The GS1 Basic UDI-DI, which is the GMN (AI 8013): "Basic UDI-DI (GMN) is used for medical device registration".
 * Checked as {@see Gmn}, check character pair included.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, sections 2.6.13, 3.9.13 and 7.9.5
 */
final class Gs1BasicUdiDi extends AbstractStringValueObject implements BasicUdiDi
{
    #[Override]
    public function issuingAgency(): IssuingAgency
    {
        return IssuingAgency::Gs1;
    }

    public function gmn(): Gmn
    {
        return new Gmn($this->value);
    }

    #[Override]
    protected function parse(string $value): string
    {
        try {
            return (new Gmn($value))->value;
        } catch (InvalidIdentificationKeyException $exception) {
            throw new InvalidUdiException('GS1 Basic UDI-DI is not a valid GMN: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
        }
    }
}
