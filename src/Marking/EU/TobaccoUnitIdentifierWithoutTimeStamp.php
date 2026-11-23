<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Marking\EU;

use DateTimeImmutable;
use DateTimeZone;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Support\AbstractStringValueObject;
use Override;

/**
 * The data-carrier content of an EU tobacco unit level UI whose time stamp is printed separately: "manufacturers and
 * importers may add the time stamp separately from the data carrier … in the format YYMMDDhh as a human readable
 * code" (Article 21(4)). It is the code the ID issuer generated (Article 8(2)): 3 to 42 alphanumeric characters, the 50
 * of Article 8(1) less the 8 of the time stamp. Trailing digits are never read as a time stamp; use
 * {@see TobaccoUnitIdentifier} for a full UI and {@see withTimeStamp()} to join the two.
 *
 * @see https://www.legislation.gov.uk/eur/2018/574/article/21/adopted Implementing Regulation (EU) 2018/574, Article 21(4)
 * @see https://www.legislation.gov.uk/eur/2018/574/article/8/adopted Article 8
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "EU: falsified medicines and tobacco"
 */
final class TobaccoUnitIdentifierWithoutTimeStamp extends AbstractStringValueObject
{
    public const int MAX_LENGTH = 42;

    private const int MIN_LENGTH = 3;

    /** The century {@see TobaccoUnitIdentifier} reads the two-digit year in. */
    private const int FIRST_YEAR = 2000;

    private const int LAST_YEAR = 2099;

    /**
     * The full UI with the hour of manufacture, converted to UTC (Article 2: time stamps are "recorded in UTC").
     */
    public function withTimeStamp(DateTimeImmutable $manufacturedAt): TobaccoUnitIdentifier
    {
        $utc = $manufacturedAt->setTimezone(new DateTimeZone('UTC'));
        $year = (int) $utc->format('Y');
        if ($year < self::FIRST_YEAR || $year > self::LAST_YEAR) {
            throw new InvalidMarkingCodeException(\sprintf('Tobacco unit level UI time stamp must be in %d-%d (UTC).', self::FIRST_YEAR, self::LAST_YEAR));
        }

        return new TobaccoUnitIdentifier($this->value . $utc->format('ymdH'));
    }

    #[Override]
    protected function parse(string $value): string
    {
        $length = \strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH || \preg_match('/^[0-9A-Za-z]+$/D', $value) !== 1) {
            throw new InvalidMarkingCodeException(\sprintf('Tobacco unit level UI without time stamp must have %d to %d alphanumeric characters.', self::MIN_LENGTH, self::MAX_LENGTH));
        }

        return $value;
    }
}
