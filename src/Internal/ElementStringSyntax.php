<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use MSSTC4PHP\ValueObject\Gs1\ElementString\UnmetRequirement;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;

/**
 * @internal
 *
 * Reads a GS1 element string in any of its notations and checks it against the AI table: data format, linters,
 * repeated AIs and the dictionary's `ex` rule; unmet `req` rules are reported, not refused. Messages name the AI and
 * the reason, never the data, and never an AI that is not in the table.
 *
 * Notations: bracketed (`(01)…(10)…`, a literal `(` in data escaped as `\(`), and unbracketed with FNC1 written as
 * GS (U+001D) or `^`, optionally after one of the symbology identifiers of GS1 AI element strings or one leading FNC1.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, sections 1.4.2, 5.1.3 (Table 5-2), 7.8.3, 7.8.4
 * @see https://github.com/gs1/gs1-syntax-engine GS1 Barcode Syntax Engine, `gs1encoders.h` and `ai.c`
 * @see docs/sources/GS1.md "Element strings"
 */
final class ElementStringSyntax
{
    public const string GS = "\x1D";

    /**
     * Section 5.7.4.1: the largest GS1 QR Code "can encode up to 7089 digits", the largest capacity the General
     * Specifications state for any GS1 data carrier; no element string is longer.
     */
    public const int MAX_DATA_LENGTH = 7089;

    private const string CARET = '^';

    /** No notation more than doubles the data: an escaped `(` is two characters, `(NN)` adds two to an AI. */
    private const int MAX_INPUT_LENGTH = 2 * self::MAX_DATA_LENGTH;

    /** @var non-empty-list<non-empty-string> Table 5-2: "Standard AI element strings" */
    private const array SYMBOLOGY_IDENTIFIERS = [']C1', ']e0', ']d2', ']Q3', ']J1'];

    /**
     * @return non-empty-list<AiElement> in the order given
     */
    public static function parse(string $input): array
    {
        if (\strlen($input) > self::MAX_INPUT_LENGTH) {
            throw self::fail(\sprintf('Element string must have at most %d characters, got %d.', self::MAX_INPUT_LENGTH, \strlen($input)));
        }

        $elements = \str_starts_with($input, '(') ? self::bracketed($input) : self::unbracketed($input);
        if ($elements === []) {
            throw self::fail('Element string is empty.');
        }

        $length = \strlen(self::barcodeData($elements));
        if ($length > self::MAX_DATA_LENGTH) {
            throw self::fail(\sprintf('Element string data must have at most %d characters, got %d.', self::MAX_DATA_LENGTH, $length));
        }

        $specs = self::checkRepeats($elements);
        self::checkExclusions($specs);

        return $elements;
    }

    /**
     * @param non-empty-list<AiElement> $elements
     *
     * @return non-empty-string
     */
    public static function bracketedForm(array $elements): string
    {
        $out = '';
        foreach ($elements as $element) {
            $out .= '(' . $element->spec->ai . ')' . self::escape($element->value);
        }

        return $out;
    }

    /**
     * @param non-empty-list<AiElement> $elements
     *
     * @return non-empty-string
     */
    public static function barcodeData(array $elements): string
    {
        $out = '';
        $last = \count($elements) - 1;
        foreach ($elements as $index => $element) {
            $out .= $element->spec->ai . $element->value;
            if ($element->spec->requiresFnc1 && $index < $last) {
                $out .= self::GS;
            }
        }

        return $out;
    }

    public static function escape(string $value): string
    {
        return \str_replace('(', '\(', $value);
    }

    public static function fail(string $message): InvalidElementStringException
    {
        return new InvalidElementStringException($message);
    }

    public static function spec(string $ai): AiSpec
    {
        return AiTable::spec($ai) ?? throw self::fail('Element string contains an unknown AI.');
    }

    /**
     * The `req` attributes no alternative of which is present: each attribute needs one of its alternatives, and an
     * alternative needs all of its AIs. GS1 evaluates them over every carrier of an item, so they are reported, not
     * refused.
     *
     * @param non-empty-list<AiElement> $elements
     *
     * @return list<UnmetRequirement>
     */
    public static function unmetRequirements(array $elements): array
    {
        $specs = [];
        foreach ($elements as $element) {
            $specs['ai' . $element->spec->ai] = $element->spec;
        }

        $unmet = [];
        foreach ($specs as $spec) {
            foreach ($spec->requires as $alternatives) {
                if (!self::anySatisfied($specs, $alternatives, $spec->ai)) {
                    $unmet[] = new UnmetRequirement($spec->ai, $alternatives);
                }
            }
        }

        return $unmet;
    }

    /**
     * @return list<AiElement>
     */
    private static function bracketed(string $input): array
    {
        $elements = [];
        $position = 0;
        $length = \strlen($input);
        while ($position < $length) {
            $close = \strpos($input, ')', $position);
            if ($close === false) {
                throw self::fail('Element string has an unclosed AI bracket.');
            }

            $spec = self::spec(\substr($input, $position + 1, $close - $position - 1));
            $value = '';
            $position = $close + 1;
            $open = \strpos($input, '(', $position);
            while ($open !== false && $input[$open - 1] === '\\') {
                $value .= \substr($input, $position, $open - $position - 1) . '(';
                $position = $open + 1;
                $open = \strpos($input, '(', $position);
            }

            $end = $open === false ? $length : $open;
            $elements[] = self::element($spec, $value . \substr($input, $position, $end - $position));
            $position = $end;
        }

        return $elements;
    }

    /**
     * Predefined-length AIs take their fixed length; every other AI runs to the next separator or the end, so data
     * whose separators were lost is never split at a guessed position.
     *
     * @return list<AiElement>
     */
    private static function unbracketed(string $input): array
    {
        $data = \str_replace(self::CARET, self::GS, self::stripPrefix($input));
        $elements = [];
        $position = 0;
        $length = \strlen($data);
        while ($position < $length) {
            if (\strspn($data, Gs1Charsets::DIGITS, $position, 1) === 0) {
                throw self::fail('Element string must start with an AI.');
            }

            $spec = AiTable::matchPrefix(\substr($data, $position, 4)) ?? throw self::fail('Element string contains an unknown AI.');
            $position += \strlen($spec->ai);
            $separator = \strpos($data, self::GS, $position);
            $end = $separator === false ? $length : $separator;
            if (!$spec->requiresFnc1) {
                $end = \min($end, $position + self::predefinedLength($spec));
            }

            $elements[] = self::element($spec, \substr($data, $position, $end - $position));
            $position = $end;
            if ($separator === $position) {
                $position++;
            }
        }

        return $elements;
    }

    private static function stripPrefix(string $input): string
    {
        if (\str_starts_with($input, ']')) {
            $identifier = \substr($input, 0, 3);
            if (!\in_array($identifier, self::SYMBOLOGY_IDENTIFIERS, true)) {
                throw self::fail('Element string has an unsupported symbology identifier.');
            }

            return \substr($input, 3);
        }

        if (\str_starts_with($input, self::GS) || \str_starts_with($input, self::CARET)) {
            return \substr($input, 1);
        }

        if ($input !== '' && \strspn($input, Gs1Charsets::DIGITS, 0, 1) === 0) {
            throw self::fail('Element string must start with an AI.');
        }

        return $input;
    }

    private static function predefinedLength(AiSpec $spec): int
    {
        $length = 0;
        foreach ($spec->components as $component) {
            $length += $component->maxLength;
        }

        return $length;
    }

    private static function element(AiSpec $spec, string $value): AiElement
    {
        if ($value === '') {
            throw self::fail(\sprintf('Element string AI (%s) has no data.', $spec->ai));
        }

        self::checkData($spec, $value);

        return new AiElement($spec, $value);
    }

    /**
     * Each component consumes the next characters up to its maximum length; optional components may be left out
     * once the data is exhausted.
     */
    private static function checkData(AiSpec $spec, string $value): void
    {
        $length = \strlen($value);
        $position = 0;
        foreach ($spec->components as $component) {
            if ($position >= $length && $component->optional) {
                break;
            }

            $part = \substr($value, $position, $component->maxLength);
            if (\strlen($part) < $component->minLength) {
                throw self::lengthFailure($spec, $length);
            }

            if (!self::inCharset($component->charset, $part)) {
                throw self::fail(\sprintf('Element string AI (%s) data contains a character outside its GS1 character set.', $spec->ai));
            }

            foreach ($component->linters as $linter) {
                if (!AiLinters::passes($linter, $part)) {
                    throw self::fail(\sprintf('Element string AI (%s) data fails the GS1 %s check.', $spec->ai, $linter));
                }
            }

            $position += \strlen($part);
        }

        if ($position < $length) {
            throw self::lengthFailure($spec, $length);
        }
    }

    private static function lengthFailure(AiSpec $spec, int $length): InvalidElementStringException
    {
        $min = 0;
        $max = 0;
        foreach ($spec->components as $component) {
            $min += $component->optional ? 0 : $component->minLength;
            $max += $component->maxLength;
        }

        $expected = $min === $max ? (string) $max : \sprintf('%d to %d', $min, $max);

        return self::fail(\sprintf('Element string AI (%s) data must have %s characters, got %d.', $spec->ai, $expected, $length));
    }

    private static function inCharset(AiCharset $charset, string $part): bool
    {
        return match ($charset) {
            AiCharset::Numeric => \strspn($part, Gs1Charsets::DIGITS) === \strlen($part),
            AiCharset::Cset82 => \strspn($part, Gs1Charsets::CSET_82) === \strlen($part),
            AiCharset::Cset39 => \strspn($part, Gs1Charsets::CSET_39) === \strlen($part),
            AiCharset::Base64Url => AiLinters::isCset64($part),
        };
    }

    /**
     * An AI may repeat (data read from several carriers of one item) only with the same data.
     *
     * @param non-empty-list<AiElement> $elements
     *
     * @return non-empty-array<non-empty-string, AiSpec> each AI once, keyed `ai<AI>` (no numeric keys)
     */
    private static function checkRepeats(array $elements): array
    {
        $values = [];
        $specs = [];
        foreach ($elements as $element) {
            $key = 'ai' . $element->spec->ai;
            if (isset($values[$key]) && $values[$key] !== $element->value) {
                throw self::fail(\sprintf('Element string repeats AI (%s) with a different value.', $element->spec->ai));
            }

            $values[$key] = $element->value;
            $specs[$key] = $element->spec;
        }

        return $specs;
    }

    /**
     * @param non-empty-array<non-empty-string, AiSpec> $specs each AI once
     */
    private static function checkExclusions(array $specs): void
    {
        foreach ($specs as $spec) {
            foreach ($spec->excludes as $pattern) {
                $other = self::find($specs, $pattern, $spec->ai);
                if ($other !== null) {
                    throw self::fail(\sprintf('Element string AI (%s) must not appear with AI (%s).', $spec->ai, $other));
                }
            }
        }
    }

    /**
     * @param array<non-empty-string, AiSpec> $present keyed `ai<AI>`
     * @param non-empty-list<non-empty-list<non-empty-string>> $alternatives
     */
    private static function anySatisfied(array $present, array $alternatives, string $self): bool
    {
        foreach ($alternatives as $group) {
            $satisfied = true;
            foreach ($group as $pattern) {
                $satisfied = $satisfied && self::find($present, $pattern, $self) !== null;
            }

            if ($satisfied) {
                return true;
            }
        }

        return false;
    }

    /**
     * The first AI other than `$self` that matches `$pattern` (`n` = any digit, same length), as the Syntax Engine's
     * `existsInAIdata` does: an AI never matches itself. A pattern expands to at most 100 AIs, each looked up in
     * `$present`, so the cost does not grow with the number of AIs.
     *
     * @param array<non-empty-string, AiSpec> $present keyed `ai<AI>`
     */
    private static function find(array $present, string $pattern, string $self): ?string
    {
        foreach (self::expand($pattern) as $ai) {
            if ($ai !== $self && isset($present['ai' . $ai])) {
                return $ai;
            }
        }

        return null;
    }

    /**
     * @return non-empty-list<string> `$pattern` with each `n` replaced by every digit, in ascending order
     */
    private static function expand(string $pattern): array
    {
        $wildcard = \strpos($pattern, 'n');
        if ($wildcard === false) {
            return [$pattern];
        }

        $ais = [];
        foreach (\str_split(Gs1Charsets::DIGITS) as $digit) {
            foreach (self::expand(\substr_replace($pattern, $digit, $wildcard, 1)) as $ai) {
                $ais[] = $ai;
            }
        }

        return $ais;
    }
}
