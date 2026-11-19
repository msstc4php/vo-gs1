<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

/**
 * @internal
 *
 * Character set of an AI data component, as the GS1 Barcode Syntax Dictionary names it
 *
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, "Type" in the file header
 */
enum AiCharset: string
{
    case Numeric = 'N';
    case Cset82 = 'X';
    case Cset39 = 'Y';
    case Base64Url = 'Z';
}
