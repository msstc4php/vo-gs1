<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Udi;

/**
 * The expiry date formats of HIBC LIC secondary data (flags `0`/`1` to `6`, the legacy five-digit Julian date) and of
 * the supplemental data identifier `14D`.
 *
 * @see https://www.hibcc.org/wp-content/uploads/SLS-2.6-Final.pdf ANSI/HIBC 2.6-2016 (R2026), Appendix E1.1, section 2.3.2.3
 */
enum HibcDateFormat: string
{
    case Mmyy = 'MMYY';
    case Mmddyy = 'MMDDYY';
    case Yymmdd = 'YYMMDD';
    case Yymmddhh = 'YYMMDDHH';
    case Yyjjj = 'YYJJJ';
    case Yyjjjhh = 'YYJJJHH';
    case Yyyymmdd = 'YYYYMMDD';
}
