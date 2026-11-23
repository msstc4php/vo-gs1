<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use DateTimeImmutable;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;

/**
 * @internal
 *
 * ISBT 128 UDIs of medical devices containing MPHO: data structures, each starting with its data identifier, either
 * after the Compound Message header `=+aabbb` or as their human-readable concatenation. The UDI begins with the
 * Processor Product Identification Code (the UDI-DI) and carries the Donation Identification Number and the Product
 * Divisions; expiration date, production date and lot number are optional; other (non-UDI) data structures may follow
 * the production identifiers and are kept, unparsed.
 *
 * @see https://iccbba.org/wp-content/uploads/2025/08/1a7593_815cf8e500b9438a860dd8fae533b2b4.pdf ICCBBA ST-017 v1.0.1, chapters 2 to 5
 * @see docs/sources/GS1.md "ICCBBA (ISBT 128)"
 *
 * @phpstan-type Kind 'ppic'|'divisions'|'din'|'expiry'|'production'|'lot'|'other'
 */
final class IsbtSyntax
{
    /** Data Structure 034: FIN(P) {A-N, P-Z, 0-9}, FPC and PDC {A-Z, 0-9}. */
    public const string PPIC = '([A-NP-Z0-9]{5})([A-Z0-9]{6})([A-Z0-9]{5})';

    /**
     * GS1 General Specifications 26.0, section 5.6.2: the largest Data Matrix holds "Numeric data: 3116 digits", the
     * most characters any carrier of an ISBT 128 compound message holds.
     */
    public const int MAX_LENGTH = 3116;

    /** "aa shall specify the number of ISBT 128 data structures that follow": two digits. */
    private const int MAX_STRUCTURES = 99;

    /** Data Structure 001: "=" followed by the first FIN character {A-N, P-Z, 1-9}. */
    private const string DIN_FIRST = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789';

    /** Table 3, the specified sequences ST-017 quotes; other RT017 numbers cannot be checked here. */
    private const array SEQUENCES = [
        '034' => ['ppic', 'divisions', 'din'],
        '035' => ['ppic', 'divisions', 'din', 'expiry'],
        '036' => ['ppic', 'divisions', 'din', 'production'],
        '037' => ['ppic', 'divisions', 'din', 'expiry', 'production'],
    ];

    private const array NAMES = [
        'ppic' => 'Processor Product Identification Code',
        'divisions' => 'Product Divisions',
        'din' => 'Donation Identification Number',
    ];

    public static function parse(string $value): IsbtData
    {
        if (\strlen($value) > self::MAX_LENGTH) {
            throw self::fail(\sprintf('must have at most %d characters', self::MAX_LENGTH));
        }

        $header = null;
        if (\preg_match('/^=\+([0-9]{2})([0-9]{3})/', $value, $match) === 1) {
            $header = $match;
            $value = \substr($value, 7);
        }

        if (!\str_starts_with($value, '=/')) {
            throw self::fail('must begin with the Processor Product Identification Code "=/"');
        }

        $structures = \preg_split('/(?=[=&])/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($structures === false || $structures === []) {
            throw self::fail('must consist of data structures');
        }

        if (\count($structures) > self::MAX_STRUCTURES) {
            throw self::fail(\sprintf('must have at most %d data structures', self::MAX_STRUCTURES));
        }

        $kinds = \array_map(self::kind(...), $structures);
        if ($header !== null) {
            self::checkHeader((int) $header[1], $header[2], $kinds);
        }

        return self::data($structures, $kinds);
    }

    /**
     * @param list<Kind> $kinds
     */
    private static function checkHeader(int $count, string $sequence, array $kinds): void
    {
        if ($count !== \count($kinds)) {
            throw self::fail('compound message data structure count does not match');
        }

        if (isset(self::SEQUENCES[$sequence]) && self::SEQUENCES[$sequence] !== $kinds) {
            throw self::fail('data structures are not in the order of the specified sequence');
        }
    }

    /**
     * @return Kind
     */
    private static function kind(string $structure): string
    {
        return match (true) {
            \str_starts_with($structure, '=+') => throw self::fail('has a compound message header after its start'),
            \str_starts_with($structure, '=/') => 'ppic',
            \str_starts_with($structure, '=,') => 'divisions',
            \str_starts_with($structure, '=>') => 'expiry',
            \str_starts_with($structure, '=}') => 'production',
            \str_starts_with($structure, '&,1') => 'lot',
            \str_starts_with($structure, '=') && \strspn($structure, self::DIN_FIRST, 1, 1) === 1 => 'din',
            default => 'other',
        };
    }

    /**
     * @param non-empty-list<non-empty-string> $structures
     * @param list<Kind> $kinds
     */
    private static function data(array $structures, array $kinds): IsbtData
    {
        $found = [];
        $other = false;
        foreach ($structures as $index => $structure) {
            $kind = $kinds[$index];
            if ($kind === 'other') {
                $other = true;
                if (\strlen($structure) < 3 || \preg_match('/^[\x21-\x7E]+$/D', $structure) !== 1) {
                    throw self::fail('has a non-UDI data structure without data or with a character that is not printable ASCII');
                }

                continue;
            }

            if ($other) {
                throw self::fail('has a UDI data structure after a non-UDI one');
            }

            if (isset($found[$kind])) {
                throw self::fail('repeats a data structure');
            }

            $found[$kind] = self::content($kind, $structure);
        }

        foreach (['ppic', 'din', 'divisions'] as $required) {
            if (!isset($found[$required])) {
                throw self::fail(\sprintf('must carry the %s', self::NAMES[$required]));
            }
        }

        if (\preg_match('/^' . self::PPIC . '$/D', $found['ppic'], $ppic) !== 1) {
            throw self::fail('Processor Product Identification Code lost its parts');
        }

        return new IsbtData(
            $structures,
            $ppic[0],
            $ppic[1],
            $ppic[2],
            $ppic[3],
            \substr($found['din'], 0, 13),
            $found['divisions'],
            isset($found['expiry']) ? self::date($found['expiry']) : null,
            isset($found['production']) ? self::date($found['production']) : null,
            $found['lot'] ?? null,
        );
    }

    /**
     * The data content after the data identifier (the DIN keeps its first character, which is part of the FIN).
     *
     * @param 'ppic'|'divisions'|'din'|'expiry'|'production'|'lot' $kind
     *
     * @return non-empty-string
     */
    private static function content(string $kind, string $structure): string
    {
        $pattern = match ($kind) {
            'ppic' => '/^=\/(' . self::PPIC . ')$/D',
            'divisions' => '/^=,([A-Z0-9]{6})$/D',
            'din' => '/^=([A-NP-Z1-9][A-NP-Z0-9]{2}[0-9]{10}[0-9A-HJ-NPR-Y]{2})$/D',
            'expiry' => '/^=>([0-9]{6})$/D',
            'production' => '/^=\}([0-9]{6})$/D',
            'lot' => '/^&,1([A-Z0-9]{1,18})$/D',
        };

        if (\preg_match($pattern, $structure, $match) !== 1) {
            throw self::fail('has a data structure with a wrong length or character');
        }

        return $match[1];
    }

    /**
     * `cyyjjj`: "019032 refers to 1 Feb 2019", so the year is 2000 + cyy.
     */
    private static function date(string $cyyjjj): DateTimeImmutable
    {
        return UdiDates::ordinal(2000 + (int) \substr($cyyjjj, 0, 3), (int) \substr($cyyjjj, 3))
            ?? throw self::fail('has a date that is not a day of its year');
    }

    private static function fail(string $reason): InvalidUdiException
    {
        return new InvalidUdiException(\sprintf('ISBT 128 UDI %s.', $reason));
    }
}
