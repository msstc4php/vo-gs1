<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

/**
 * @internal
 *
 * The GS1 character sets that AI data components name with "X" (CSET 82) and "Y" (CSET 39)
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, Table 7-20 (CSET 82) and Table 7-21 (CSET 39)
 * @see https://github.com/gs1/gs1-syntax-dictionary GS1 Barcode Syntax Dictionary, "Type" in the file header
 */
final class Gs1Charsets
{
    public const string CSET_82 = '!"%&\'()*+,-./0123456789:;<=>?ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';

    public const string CSET_39 = '#-/0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public const string DIGITS = '0123456789';
}
