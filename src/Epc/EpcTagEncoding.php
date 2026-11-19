<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Epc;

/**
 * The EPC binary coding schemes that have an EPC Tag URI, named as in the URI. The encodings of TDS 2.0 and later
 * (`sgtin+`, `sgtin++`, …) define no tag URI.
 *
 * @see https://ref.gs1.org/standards/tds/ EPC Tag Data Standard 2.3, section 12.4 (grammar) and Table 12-2
 */
enum EpcTagEncoding: string
{
    case Sgtin96 = 'sgtin-96';
    case Sgtin198 = 'sgtin-198';
    case Sscc96 = 'sscc-96';
    case Sgln96 = 'sgln-96';
    case Sgln195 = 'sgln-195';
    case Grai96 = 'grai-96';
    case Grai170 = 'grai-170';
    case Giai96 = 'giai-96';
    case Giai202 = 'giai-202';
    case Gsrn96 = 'gsrn-96';
    case Gsrnp96 = 'gsrnp-96';
    case Gdti96 = 'gdti-96';
    case Gdti113 = 'gdti-113';
    case Gdti174 = 'gdti-174';
    case Cpi96 = 'cpi-96';
    case CpiVar = 'cpi-var';
    case Sgcn96 = 'sgcn-96';
    case Itip110 = 'itip-110';
    case Itip212 = 'itip-212';
    case Gid96 = 'gid-96';
    case Usdod96 = 'usdod-96';
    case AdiVar = 'adi-var';

    public function scheme(): EpcScheme
    {
        return EpcScheme::from(\substr($this->value, 0, (int) \strpos($this->value, '-')));
    }

    /**
     * Every encoding but GID-96 carries a filter value (TDS section 10).
     */
    public function hasFilter(): bool
    {
        return $this !== self::Gid96;
    }

    /**
     * The width of the filter field: 6 bits for ADI-var, 3 for the others; null where TDS does not define it (GID-96
     * has no filter, USDOD-96 defers to the US DoD guide).
     *
     * @return 3|6|null
     */
    public function filterBits(): ?int
    {
        return match ($this) {
            self::Gid96, self::Usdod96 => null,
            self::AdiVar => 6,
            default => 3,
        };
    }

    /**
     * Whether the encoding uses a partition table: the GS1 Company Prefix length decides the partition value.
     */
    public function isPartitioned(): bool
    {
        return $this->scheme()->hasElementString();
    }
}
