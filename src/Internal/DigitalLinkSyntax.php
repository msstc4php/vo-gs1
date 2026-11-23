<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementStringBuilder;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidDigitalLinkException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;
use MSSTC4PHP\ValueObject\Gs1\Key\Cpid;
use MSSTC4PHP\ValueObject\Gs1\Key\Gcn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gdti;
use MSSTC4PHP\ValueObject\Gs1\Key\Giai;
use MSSTC4PHP\ValueObject\Gs1\Key\Ginc;
use MSSTC4PHP\ValueObject\Gs1\Key\Gln;
use MSSTC4PHP\ValueObject\Gs1\Key\Gmn;
use MSSTC4PHP\ValueObject\Gs1\Key\Grai;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsin;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsrn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Key\IdentificationKey;
use MSSTC4PHP\ValueObject\Gs1\Key\Sscc;
use MSSTC4PHP\ValueObject\Network\Url;

/**
 * @internal
 *
 * Reads and writes GS1 Digital Link URIs: the primary key is the last `/AI/value` pair of the path whose AI is a
 * primary key, scanning back from the end, as the GS1 Barcode Syntax Engine does; the key qualifiers must follow the
 * dictionary's ordered sequences (`dlpkey`); numeric query keys are AIs, every other query parameter is kept
 *
 * @see https://ref.gs1.org/standards/digital-link/uri-syntax/ GS1 Digital Link Standard: URI Syntax 1.7.0, section 4
 * @see https://github.com/gs1/gs1-syntax-engine `src/c-lib/dl.c`, `gs1_parseDLuri()`, commit 58c1dc8
 */
final class DigitalLinkSyntax
{
    public const string CANONICAL_STEM = 'https://id.gs1.org';

    // A package bound against oversized input, not a GS1 rule: twice the largest GS1 QR Code capacity (7089).
    public const int MAX_LENGTH = 14178;

    /**
     * The deprecated convenience alphas of the path and their AIs, as the GS1 Barcode Syntax Engine maps them
     * (`alpha_ai_map` in `src/c-lib/dl.c`).
     */
    private const array CONVENIENCE_ALPHAS = [
        'cpid' => '8010', 'cpsn' => '8011', 'cpv' => '22', 'gcn' => '255', 'gdti' => '253', 'giai' => '8004',
        'ginc' => '401', 'gln' => '414', 'glnx' => '254', 'gmn' => '8013', 'grai' => '8003', 'gsin' => '402',
        'gsrn' => '8018', 'gsrnp' => '8017', 'gtin' => '01', 'itip' => '8006', 'lot' => '10', 'party' => '417',
        'refno' => '8020', 'ser' => '21', 'srin' => '8019', 'sscc' => '00',
    ];

    /** AIs whose value is a GTIN: a legacy link may give it with 8, 12 or 13 digits. */
    private const array GTIN_AIS = ['01', '02', '03'];

    // RFC 3986 section 2.3.
    private const string UNRESERVED = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';

    // RFC 3986 section 2: unreserved, reserved and "%".
    private const string URI_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&\'()*+,;=%';

    public static function parse(string $uri): DigitalLinkParts
    {
        if (\strlen($uri) > self::MAX_LENGTH) {
            throw self::fail(\sprintf('is longer than %d characters', self::MAX_LENGTH));
        }

        if (\strspn($uri, self::URI_CHARACTERS) !== \strlen($uri)) {
            throw self::fail('contains a character that a URI does not allow');
        }

        // RFC 3986 section 2.1: pct-encoded = "%" HEXDIG HEXDIG.
        if (\preg_match('/%(?![0-9A-Fa-f]{2})/', $uri) === 1) {
            throw self::fail('contains a "%" that is not followed by two hex digits');
        }

        $url = Url::tryFrom($uri);
        $schemeEnd = \strpos($uri, '://');
        if (!$url instanceof Url || $schemeEnd === false) {
            throw self::fail('is not an http or https URL');
        }

        $rest = \substr($uri, $schemeEnd + 3);
        $authority = \substr($rest, 0, \strcspn($rest, '/?#'));
        $rest = \substr($rest, \strlen($authority));
        // RFC 3986 section 3.2.1: userinfo cannot hold an unescaped "@".
        if (\substr_count($authority, '@') > 1) {
            throw self::fail('authority has more than one "@"');
        }

        $fragment = \strstr($rest, '#');
        if ($fragment !== false) {
            $rest = \substr($rest, 0, -\strlen($fragment));
        }

        $query = \strstr($rest, '?');
        $path = $query === false ? $rest : \substr($rest, 0, -\strlen($query));

        [$stemPath, $elements] = self::path($path);
        [$attributes, $extensions, $elements] = $query === false ? [[], [], $elements] : self::query(\substr($query, 1), $elements);

        // Lower-casing from the "@" on leaves the "@" itself as it is.
        $hostStart = (int) \strpos($authority, '@');
        $authority = \substr($authority, 0, $hostStart) . \strtolower(\substr($authority, $hostStart));

        return new DigitalLinkParts(\strtolower($url->scheme()), $authority, $stemPath, $elements, $attributes, $extensions, $fragment === false ? null : $fragment);
    }

    public static function elementString(DigitalLinkParts $parts): ElementString
    {
        try {
            $builder = new ElementStringBuilder();
            foreach ([...$parts->path, ...$parts->attributes] as $element) {
                $builder = $builder->with($element->spec->ai, $element->value);
            }

            return $builder->build();
        } catch (InvalidElementStringException $exception) {
            throw new InvalidDigitalLinkException('GS1 Digital Link data is not a valid element string: ' . $exception->getMessage(), $exception->getCode(), previous: $exception);
        }
    }

    /**
     * @param non-empty-list<AiElement> $path
     * @param list<AiElement> $attributes
     *
     * @return non-empty-string
     */
    public static function render(string $stem, array $path, array $attributes): string
    {
        $uri = \rtrim($stem, '/');
        foreach ($path as $element) {
            $uri .= '/' . $element->spec->ai . '/' . self::escape($element->value);
        }

        $query = \array_map(static fn (AiElement $element): string => $element->spec->ai . '=' . self::escape($element->value), $attributes);

        return $query === [] ? $uri : $uri . '?' . \implode('&', $query);
    }

    /**
     * The first primary key AI, then the longest qualifier sequence the dictionary allows that the data carries (the
     * first such sequence on a tie); every other AI is an attribute, in the order given.
     *
     * @param list<AiElement> $elements each AI once
     *
     * @return array{non-empty-list<AiElement>, list<AiElement>} path and attributes
     */
    public static function arrange(array $elements): array
    {
        $primary = null;
        foreach ($elements as $element) {
            if ($element->spec->digitalLinkPrimaryKey !== null) {
                $primary = $element;
                break;
            }
        }

        if ($primary === null) {
            throw new InvalidDigitalLinkException('GS1 Digital Link needs a primary key AI in the element string.');
        }

        $byAi = [];
        foreach ($elements as $element) {
            $byAi[$element->spec->ai] = $element;
        }

        $candidates = [];
        foreach ([[], ...$primary->spec->digitalLinkPrimaryKey ?? []] as $sequence) {
            $present = \array_values(\array_intersect($sequence, \array_keys($byAi)));
            $path = [$primary, ...\array_map(static fn (string $ai): AiElement => $byAi[$ai], $present)];
            $attributes = \array_values(\array_filter($elements, static fn (AiElement $element): bool => !\in_array($element, $path, true)));
            $fits = \array_all($attributes, static fn (AiElement $element): bool => $element->spec->digitalLinkAttribute);
            $candidates[] = [\count($path), $fits, $path, $attributes];
        }

        // Longest first; on a tie, a sequence whose leftover AIs can all go to the query; then the dictionary's order.
        \usort($candidates, static fn (array $a, array $b): int => [$b[0], $b[1]] <=> [$a[0], $a[1]]);

        return [$candidates[0][2], $candidates[0][3]];
    }

    public static function keyFor(AiElement $element): IdentificationKey
    {
        $value = $element->value;

        return match ($element->spec->ai) {
            '00' => new Sscc($value),
            '01' => new Gtin($value),
            '8006' => new Gtin(\substr($value, 0, 14)),
            '253' => new Gdti($value),
            '255' => new Gcn($value),
            '401' => new Ginc($value),
            '402' => new Gsin($value),
            '414', '415', '417' => new Gln($value),
            '8003' => new Grai($value),
            '8004' => new Giai($value),
            '8010' => new Cpid($value),
            '8013' => new Gmn($value),
            '8017', '8018' => new Gsrn($value),
            default => throw new LogicException('No identification key class for this AI.'),
        };
    }

    /**
     * RFC 3986 section 6.2.2: upper-case hex digits in escapes and decode the escapes of unreserved characters, so
     * equivalent spellings compare equal.
     */
    public static function normalisedEscapes(string $value): string
    {
        return (string) \preg_replace_callback('/%([0-9A-Fa-f]{2})/', static function (array $match): string {
            $character = \chr((int) \hexdec($match[1]));

            return \strspn($character, self::UNRESERVED) === 1 ? $character : '%' . \strtoupper($match[1]);
        }, $value);
    }

    /**
     * Percent-encodes every character but the RFC 3986 unreserved ones, as the GS1 Barcode Syntax Engine's
     * `URIescape()` does.
     */
    private static function escape(string $value): string
    {
        return (string) \preg_replace_callback('/[^A-Za-z0-9\-._~]/', static fn (array $match): string => \sprintf('%%%02X', \ord($match[0])), $value);
    }

    private static function fail(string $reason): InvalidDigitalLinkException
    {
        return new InvalidDigitalLinkException('GS1 Digital Link ' . $reason . '.');
    }

    /**
     * @return array{string, non-empty-list<AiElement>} the stem path and the GS1 path
     */
    private static function path(string $path): array
    {
        $segments = \explode('/', $path);
        // A trailing slash is "not-conformant" but "should be tolerated by resolvers" (URI Syntax 1.7.0, 4.12).
        if (\end($segments) === '') {
            \array_pop($segments);
        }

        $specs = [];
        $key = null;
        for ($i = \count($segments) - 2; $i >= 1; $i -= 2) {
            $name = self::normalisedEscapes($segments[$i]);
            $spec = AiTable::spec(self::CONVENIENCE_ALPHAS[$name] ?? $name);
            if (!$spec instanceof AiSpec) {
                break;
            }

            $specs[$i] = $spec;
            if ($spec->digitalLinkPrimaryKey !== null) {
                $key = $i;
                break;
            }
        }

        if ($key === null) {
            throw self::isCompressed((string) \end($segments)) ? self::fail('is compressed; GS1 Digital Link URI Syntax 1.7.0 defines no compression') : self::fail('has no GS1 primary key in its path');
        }

        $elements = [self::element($specs[$key], $segments[$key + 1], false)];
        $qualifiers = [];
        for ($i = $key + 2; $i < \count($segments); $i += 2) {
            $elements[] = self::element($specs[$i], $segments[$i + 1], false);
            $qualifiers[] = $specs[$i]->ai;
        }

        if (!self::isKeySequence($specs[$key], $qualifiers)) {
            throw self::fail('path has key qualifiers that its primary key does not take in this order');
        }

        return [\implode('/', \array_slice($segments, 0, $key)), $elements];
    }

    /**
     * @param non-empty-list<AiElement> $path
     *
     * @return array{list<AiElement>, list<string>, non-empty-list<AiElement>} attributes, extensions and the path with
     *                                                                         the qualifiers read from the query
     */
    private static function query(string $query, array $path): array
    {
        $pathAis = \array_map(static fn (AiElement $element): string => $element->spec->ai, $path);
        $qualifiers = \array_slice($pathAis, 1);
        $attributes = [];
        $extensions = [];
        foreach (\explode('&', \str_replace(';', '&', $query)) as $parameter) {
            $equals = \strpos($parameter, '=');
            $name = self::normalisedEscapes($equals === false ? $parameter : \substr($parameter, 0, $equals));
            if ($name === '' || \strspn($name, Gs1Charsets::DIGITS) !== \strlen($name)) {
                if ($parameter !== '') {
                    $extensions[] = $parameter;
                }

                continue;
            }

            $spec = AiTable::spec($name) ?? throw self::fail('query has an all-numeric key that is no AI');
            if (\in_array($spec->ai, $pathAis, true)) {
                throw self::fail('has an AI in the path and in the query');
            }

            // A repeated attribute "should not" appear; if it does, "the last defined value … takes precedence".
            unset($attributes['ai' . $spec->ai]);
            $attributes['ai' . $spec->ai] = self::element($spec, $equals === false ? '' : \substr($parameter, $equals + 1), true);
        }

        // URI Syntax 1.7.0 has no SHALL that keeps a key qualifier out of the query, so one that fits the path's
        // qualifier sequence is read as the qualifier it is.
        $byAi = [];
        foreach ([...$path, ...$attributes] as $element) {
            $byAi[$element->spec->ai] = $element;
        }

        foreach ($attributes as $name => $attribute) {
            $order = self::sequenceWith($path[0]->spec, $qualifiers, $attribute->spec->ai);
            if ($order !== null) {
                $qualifiers = $order;
                unset($attributes[$name]);
            } elseif (!$attribute->spec->digitalLinkAttribute) {
                throw self::fail('query has an AI that is not a GS1 Digital Link data attribute');
            }
        }

        return [\array_values($attributes), $extensions, [$path[0], ...\array_map(static fn (string $ai): AiElement => $byAi[$ai], $qualifiers)]];
    }

    private static function element(AiSpec $spec, string $raw, bool $inQuery): AiElement
    {
        $value = \rawurldecode($inQuery ? \str_replace('+', ' ', $raw) : $raw);
        if ($value === '') {
            throw $inQuery ? self::fail('query has an AI without a value') : self::fail('path has an empty value');
        }

        if (\str_contains($value, "\0")) {
            throw self::fail('data contains a NUL character');
        }

        // Before 1.4.0 a GTIN-8, -12 or -13 could appear without its leading zeros (URI Syntax 1.7.0, 4.1).
        if (\in_array($spec->ai, self::GTIN_AIS, true) && \in_array(\strlen($value), [8, 12, 13], true)) {
            $value = \str_pad($value, 14, '0', STR_PAD_LEFT);
        }

        return new AiElement($spec, $value);
    }

    /**
     * Whether some qualifier sequence of the primary key holds `$qualifiers` in their order, as the engine accepts
     * any ordered subset of a `dlpkey` sequence.
     *
     * @param list<string> $qualifiers
     */
    private static function isKeySequence(AiSpec $primary, array $qualifiers): bool
    {
        foreach ($primary->digitalLinkPrimaryKey ?? [] as $sequence) {
            if (\array_values(\array_intersect($sequence, $qualifiers)) === $qualifiers) {
                return true;
            }
        }

        return $qualifiers === [];
    }

    /**
     * The qualifiers with `$ai` added, in the order of the first sequence of the primary key that holds `$ai` and the
     * present qualifiers in their order; null when no sequence does.
     *
     * @param list<string> $qualifiers
     *
     * @return list<string>|null
     */
    private static function sequenceWith(AiSpec $primary, array $qualifiers, string $ai): ?array
    {
        foreach ($primary->digitalLinkPrimaryKey ?? [] as $sequence) {
            if (\in_array($ai, $sequence, true) && \array_values(\array_intersect($sequence, $qualifiers)) === $qualifiers) {
                return \array_values(\array_intersect($sequence, [...$qualifiers, $ai]));
            }
        }

        return null;
    }

    /**
     * RE2 and RE3 of URI Syntax 1.7.0, section 6.1.2, applied to the last path segment. RE2 also matches a plain
     * number (a GTIN after a convenience alpha); only the message depends on it, so digits alone do not count.
     */
    private static function isCompressed(string $segment): bool
    {
        return \preg_match('/^(?:(?=[0-9]*[A-Za-z_-])[0-9A-Za-z_-]{10,}|eh[0-9a-f]*|ex[A-Za-z0-9_-]*)$/', $segment) === 1;
    }
}
