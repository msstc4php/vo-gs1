<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\Ppn;

/**
 * @internal
 *
 * The IFA (ASC) format of the FMD pack code: an ISO/IEC 15434 format 06 message of ASC MH10.8.2 data identifiers,
 * separated by GS. DI 9N (PPN), S (serial), 1T (batch) and D (expiry) are mandatory and checked against Appendix A;
 * 8P (GTIN) may repeat; further DIs are kept as given ("The sequence of the data elements is discretionary").
 *
 * @see https://www.ifaffm.de/mandanten/1/documents/04_ifa_coding_system/IFA_Spec_PPN_Code_Handelspackung_EN.pdf IFA, PPN-Code Specification for Retail Packaging, Version 3.06, sections 5.1-5.3, Appendix A, Appendix C.5
 */
final readonly class IfaPackData
{
    public const string HEADER = "[)>\x1E06\x1D";

    private const string TRAILER = "\x1E\x04";

    private const string RS = "\x1E";

    private const string FIELD = '/^([0-9]{0,3}[A-Z])([\x20-\x7E]+)$/D';

    private const string TYPE = 'FMD pack code (IFA)';

    /** @var non-empty-list<non-empty-string> */
    private const array MANDATORY = ['9N', 'S', '1T', 'D'];

    private const int MAX_TEXT_LENGTH = 20;

    /**
     * @param non-empty-list<array{non-empty-string, non-empty-string}> $fields data identifier and data, in the order given
     */
    private function __construct(
        public array $fields,
    ) {
    }

    public static function parse(string $input): self
    {
        $body = \substr($input, \strlen(self::HEADER));
        if (\str_ends_with($body, self::TRAILER)) {
            $body = \substr($body, 0, -\strlen(self::TRAILER));
        } elseif (\str_ends_with($body, self::RS)) {
            $body = \substr($body, 0, -1);
        }

        $fields = [];
        $counts = \array_fill_keys(self::MANDATORY, 0);
        foreach (\explode(ElementStringSyntax::GS, $body) as $field) {
            if (\preg_match(self::FIELD, $field, $match) !== 1) {
                throw new InvalidMarkingCodeException(self::TYPE . ' has a field without a data identifier.');
            }

            self::check($match[1], $match[2]);
            if (isset($counts[$match[1]])) {
                $counts[$match[1]]++;
            }

            $fields[] = [$match[1], $match[2]];
        }

        if (\array_filter($counts, static fn (int $count): bool => $count !== 1) !== []) {
            throw new InvalidMarkingCodeException(self::TYPE . ' must carry DI 9N, S, 1T and D once each.');
        }

        return new self($fields);
    }

    /**
     * @return non-empty-string
     */
    public function canonical(): string
    {
        return self::HEADER . \implode(ElementStringSyntax::GS, \array_map(static fn (array $field): string => $field[0] . $field[1], $this->fields)) . self::TRAILER;
    }

    /**
     * @return non-empty-string
     */
    public function get(string $dataIdentifier): string
    {
        foreach ($this->fields as [$identifier, $data]) {
            if ($identifier === $dataIdentifier) {
                return $data;
            }
        }

        throw new LogicException('IFA pack data lost a mandatory data identifier.');
    }

    /**
     * Section 5.2.4: "the dates are for the 21st century (2000–2099)"; DD 00 is month and year only, read as the
     * last day of the month like GS1's AI (17).
     */
    public static function expiryDate(string $yymmdd): DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        $date = new DateTimeImmutable(\sprintf('20%s-%s-01T00:00:00', \substr($yymmdd, 0, 2), \substr($yymmdd, 2, 2)), $utc);
        $day = (int) \substr($yymmdd, 4, 2);

        return $day === 0 ? $date->modify('last day of this month') : $date->setDate((int) $date->format('Y'), (int) $date->format('m'), $day);
    }

    private static function check(string $identifier, string $data): void
    {
        match ($identifier) {
            '9N' => self::checkPpn($data),
            'S', '1T' => self::checkText($identifier, $data),
            'D' => self::checkDate($data),
            '8P' => self::checkGtin($data),
            default => null,
        };
    }

    private static function checkPpn(string $data): void
    {
        try {
            new Ppn($data);
        } catch (InvalidMarkingCodeException $exception) {
            throw new InvalidMarkingCodeException(self::TYPE . ' DI 9N is not a valid PPN: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
        }
    }

    /**
     * Appendix A: 1 to 20 characters; footnote 35: the technically processed characters are GS1's set 82.
     */
    private static function checkText(string $identifier, string $data): void
    {
        $length = \strlen($data);
        if ($length > self::MAX_TEXT_LENGTH || \strspn($data, Gs1Charsets::CSET_82) !== $length) {
            throw new InvalidMarkingCodeException(\sprintf('%s DI %s must have 1 to %d characters of GS1 character set 82, got %d characters.', self::TYPE, $identifier, self::MAX_TEXT_LENGTH, $length));
        }
    }

    private static function checkDate(string $data): void
    {
        if (\strlen($data) !== 6 || !Gs1Dates::isDate('20' . $data, true)) {
            throw new InvalidMarkingCodeException(self::TYPE . ' DI D must be a date YYMMDD (DD 00 allowed).');
        }
    }

    private static function checkGtin(string $data): void
    {
        if (\strlen($data) !== 14 || !Gtin::isValid($data)) {
            throw new InvalidMarkingCodeException(self::TYPE . ' DI 8P must be a GTIN of 14 digits.');
        }
    }
}
