<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Internal;

use MSSTC4PHP\ValueObject\Gs1\ElementString\UnmetRequirement;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidElementStringException;

/**
 * @internal
 *
 * Reads a GS1 element string in any of its notations and checks it against the AI table: data format, linters,
 * repeated AIs and the dictionary's `ex` rule; unmet `req` rules are reported, not refused. Messages name the reason
 * and fixed bounds only: no data, no AI number, no length of the input.
 *
 * Notations: bracketed (`(01)…(10)…`, a literal `(` in data escaped as `\(`), and unbracketed with FNC1 written as
 * GS (U+001D) or `^`, optionally after one of the symbology identifiers of GS1 AI element strings or one leading FNC1.
 *
 * @see https://ref.gs1.org/standards/genspecs/ GS1 General Specifications 26.0, sections 1.4.2, 5.1.3 (Table 5-2), 7.8.3, 7.8.4
 * @see https://github.com/gs1/gs1-syntax-engine GS1 Barcode Syntax Engine, `gs1encoders.h` and `ai.c`
 * @see https://github.com/msstc4php/vo-gs1/blob/main/docs/sources/GS1.md "Element strings"
 */
final class ElementStringSyntax
{
    public const string GS = "\x1D";

    /**
     * A package bound against oversized input and for storage, not a GS1 rule: twice the largest GS1 QR Code
     * capacity (section 5.7.4.1, "up to 7089 digits"), so data spread over several carriers fits. It bounds both the
     * input and the canonical bracketed form.
     */
    public const int MAX_LENGTH = 14178;

    private const string CARET = '^';

    /** @var non-empty-list<non-empty-string> Table 5-2: "Standard AI element strings" */
    private const array SYMBOLOGY_IDENTIFIERS = [']C1', ']e0', ']d2', ']Q3', ']J1'];

    /**
     * @return non-empty-list<AiElement> in the order given
     */
    public static function parse(string $input): array
    {
        if (\strlen($input) > self::MAX_LENGTH) {
            throw self::fail(\sprintf('Element string must have at most %d characters.', self::MAX_LENGTH));
        }

        $elements = \str_starts_with($input, '(') ? self::bracketed($input) : self::unbracketed($input);
        if ($elements === []) {
            throw self::fail('Element string is empty.');
        }

        if (\strlen(self::bracketedForm($elements)) > self::MAX_LENGTH) {
            throw self::fail(\sprintf('Element string must have at most %d characters in its bracketed form.', self::MAX_LENGTH));
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
     * Whether every component passes its {@see AiLinters::REPORTED} linters. The elements were checked by
     * {@see parse()}, so each component takes the next characters up to its maximum length; an omitted optional
     * component is not linted.
     *
     * @param non-empty-list<AiElement> $elements
     */
    public static function passesReportedLinters(array $elements): bool
    {
        foreach ($elements as $element) {
            $position = 0;
            foreach ($element->spec->components as $component) {
                $part = \substr($element->value, $position, $component->maxLength);
                $position += \strlen($part);
                foreach ($component->linters as $linter) {
                    if ($part !== '' && !AiLinters::passesReported($linter, $part)) {
                        return false;
                    }
                }
            }
        }

        return true;
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
            throw self::fail('Element string has an AI without data.');
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
                throw self::lengthFailure();
            }

            if (!self::inCharset($component->charset, $part)) {
                throw self::fail('Element string AI data contains a character outside its GS1 character set.');
            }

            foreach ($component->linters as $linter) {
                if (!AiLinters::passes($linter, $part)) {
                    throw self::fail('Element string AI data fails a GS1 check of its AI.');
                }
            }

            $position += \strlen($part);
        }

        if ($position < $length) {
            throw self::lengthFailure();
        }
    }

    private static function lengthFailure(): InvalidElementStringException
    {
        return self::fail('Element string AI data has the wrong length for its AI.');
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
                throw self::fail('Element string repeats an AI with a different value.');
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
                if (self::find($specs, $pattern, $spec->ai) !== null) {
                    throw self::fail('Element string has two AIs that must not appear together.');
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
