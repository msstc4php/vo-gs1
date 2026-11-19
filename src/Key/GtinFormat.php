<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Key;

/**
 * The four GTIN lengths: a GTIN "may be an eight, twelve, thirteen or fourteen-digit string".
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, section 1.3.1
 * @see docs/sources/GS1.md "GTIN formats and the 14-digit form"
 */
enum GtinFormat: int
{
    case Gtin8 = 8;
    case Gtin12 = 12;
    case Gtin13 = 13;
    case Gtin14 = 14;

    /**
     * @return 8|12|13|14
     */
    public function length(): int
    {
        return $this->value;
    }
}
