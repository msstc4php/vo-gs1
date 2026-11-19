<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Epc;

/**
 * The EPC schemes with a pure identity URI (`urn:epc:id:<scheme>:…`), named as in the URI.
 *
 * @see https://ref.gs1.org/standards/tds/ EPC Tag Data Standard 2.3, section 6.3, Figure 6-1
 */
enum EpcScheme: string
{
    case Sgtin = 'sgtin';
    case Sscc = 'sscc';
    case Sgln = 'sgln';
    case Grai = 'grai';
    case Giai = 'giai';
    case Gsrn = 'gsrn';
    case Gsrnp = 'gsrnp';
    case Gdti = 'gdti';
    case Cpi = 'cpi';
    case Sgcn = 'sgcn';
    case Ginc = 'ginc';
    case Gsin = 'gsin';
    case Itip = 'itip';
    case Upui = 'upui';
    case Pgln = 'pgln';
    case Gid = 'gid';
    case Usdod = 'usdod';
    case Adi = 'adi';
    case Bic = 'bic';
    case Imovn = 'imovn';

    /**
     * Whether TDS section 7 maps the scheme to a GS1 element string; such a scheme starts with a GS1 Company Prefix.
     */
    public function hasElementString(): bool
    {
        return match ($this) {
            self::Gid, self::Usdod, self::Adi, self::Bic, self::Imovn => false,
            default => true,
        };
    }
}
