<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

/**
 * The segment lengths (labeler-product-package) 21 CFR 207.33(b)(2) permits for a 10- or 11-digit NDC.
 *
 * @see https://www.ecfr.gov/current/title-21/chapter-I/subchapter-C/part-207/subpart-B/section-207.33 21 CFR 207.33(b)(2)
 */
enum NdcConfiguration: string
{
    case FourFourTwo = '4-4-2';
    case FiveThreeTwo = '5-3-2';
    case FiveFourOne = '5-4-1';
    case SixThreeTwo = '6-3-2';
    case SixFourOne = '6-4-1';

    /**
     * @return 4|5|6
     */
    public function labelerLength(): int
    {
        return match ($this) {
            self::FourFourTwo => 4,
            self::FiveThreeTwo, self::FiveFourOne => 5,
            self::SixThreeTwo, self::SixFourOne => 6,
        };
    }

    /**
     * @return 3|4
     */
    public function productLength(): int
    {
        return match ($this) {
            self::FiveThreeTwo, self::SixThreeTwo => 3,
            self::FourFourTwo, self::FiveFourOne, self::SixFourOne => 4,
        };
    }

    /**
     * @return 1|2
     */
    public function packageLength(): int
    {
        return match ($this) {
            self::FiveFourOne, self::SixFourOne => 1,
            self::FourFourTwo, self::FiveThreeTwo, self::SixThreeTwo => 2,
        };
    }

    /**
     * @return 10|11
     */
    public function length(): int
    {
        return $this->labelerLength() === 6 ? 11 : 10;
    }
}
