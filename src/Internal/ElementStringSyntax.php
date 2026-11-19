<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;

/**
 * @internal
 *
 * Reads a GS1 element string in any of its notations and checks it against the AI table: data format, linters,
 * repeated AIs and the dictionary's `ex` and `req` association rules. Messages name the AI and the reason, never the
 * data, and never an AI that is not in the table.
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

    private const string CARET = '^';

    /** @var non-empty-list<non-empty-string> Table 5-2: "Standard AI element strings" */
    private const array SYMBOLOGY_IDENTIFIERS = [']C1', ']e0', ']d2', ']Q3', ']J1'];

    /**
     * @return non-empty-list<AiElement> in the order given
     */
    public static function parse(string $input): array
    {
        $elements = \str_starts_with($input, '(') ? self::bracketed($input) : self::unbracketed($input);
        if ($elements === []) {
            throw self::fail('Element string is empty.');
        }

        self::checkRepeats($elements);
        self::checkExclusions($elements);
        self::checkRequirements($elements);

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
     */
    private static function checkRepeats(array $elements): void
    {
        $seen = [];
        foreach ($elements as $element) {
            $key = 'ai' . $element->spec->ai;
            if (isset($seen[$key]) && $seen[$key] !== $element->value) {
                throw self::fail(\sprintf('Element string repeats AI (%s) with a different value.', $element->spec->ai));
            }

            $seen[$key] = $element->value;
        }
    }

    /**
     * @param non-empty-list<AiElement> $elements
     */
    private static function checkExclusions(array $elements): void
    {
        foreach ($elements as $element) {
            foreach ($element->spec->excludes as $pattern) {
                $other = self::find($elements, $pattern, $element->spec->ai);
                if ($other !== null) {
                    throw self::fail(\sprintf('Element string AI (%s) must not appear with AI (%s).', $element->spec->ai, $other));
                }
            }
        }
    }

    /**
     * Each `req` attribute needs one of its alternatives, and an alternative needs all of its AIs.
     *
     * @param non-empty-list<AiElement> $elements
     */
    private static function checkRequirements(array $elements): void
    {
        foreach ($elements as $element) {
            foreach ($element->spec->requires as $alternatives) {
                if (!self::anySatisfied($elements, $alternatives, $element->spec->ai)) {
                    $names = [];
                    foreach ($alternatives as $group) {
                        $names[] = '(' . \implode(')+(', $group) . ')';
                    }

                    throw self::fail(\sprintf('Element string AI (%s) requires %s.', $element->spec->ai, \implode(' or ', $names)));
                }
            }
        }
    }

    /**
     * @param non-empty-list<AiElement> $elements
     * @param non-empty-list<non-empty-list<non-empty-string>> $alternatives
     */
    private static function anySatisfied(array $elements, array $alternatives, string $self): bool
    {
        foreach ($alternatives as $group) {
            $satisfied = true;
            foreach ($group as $pattern) {
                $satisfied = $satisfied && self::find($elements, $pattern, $self) !== null;
            }

            if ($satisfied) {
                return true;
            }
        }

        return false;
    }

    /**
     * The first AI other than `$self` that matches `$pattern` (`n` = any digit, same length), as the Syntax Engine's
     * `existsInAIdata` does: an AI never matches itself.
     *
     * @param non-empty-list<AiElement> $elements
     */
    private static function find(array $elements, string $pattern, string $self): ?string
    {
        $regex = '/^' . \str_replace('n', '\d', $pattern) . '$/';
        foreach ($elements as $element) {
            if ($element->spec->ai !== $self && \preg_match($regex, $element->spec->ai) === 1) {
                return $element->spec->ai;
            }
        }

        return null;
    }
}
