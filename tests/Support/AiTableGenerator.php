<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Support;

use UnexpectedValueException;

/**
 * Turns the GS1 Barcode Syntax Dictionary into `src/Internal/AiTable.php`. Anything in the dictionary this generator
 * does not understand (a new flag, attribute or component syntax) throws, so an update cannot silently drop a rule.
 *
 * @phpstan-type ComponentTuple array{0: 'N'|'X'|'Y'|'Z', 1: positive-int, 2: positive-int, 3: bool, 4: list<non-empty-string>}
 * @phpstan-type SpecTuple array{
 *     0: non-empty-string|null,
 *     1: bool,
 *     2: bool,
 *     3: non-empty-list<ComponentTuple>,
 *     4: list<non-empty-list<non-empty-list<non-empty-string>>>,
 *     5: list<non-empty-string>,
 *     6: list<non-empty-list<non-empty-string>>|null,
 * }
 */
final class AiTableGenerator
{
    public const string DICTIONARY = 'resources/gs1-syntax-dictionary.txt';

    public const string TARGET = 'src/Internal/AiTable.php';

    /** The dictionary release vendored under resources/, and the upstream commit of its tag. */
    public const string RELEASE = '2026-01-27';

    public const string COMMIT = '8ad003f83a4ba6c2179ce15d578fae27d6580909';

    private const array CHARSETS = ['N' => 'Numeric', 'X' => 'Cset82', 'Y' => 'Cset39', 'Z' => 'Base64Url'];

    private const string AI_PATTERN = '/^[0-9n]{2,4}$/D';

    private const string COMPONENT = '/^(\[)?([NXYZ])(?:([1-9][0-9]*)|\.\.([1-9][0-9]*))(\])?((?:,[a-z0-9]+)*)$/D';

    public function generate(string $dictionary): string
    {
        $release = \preg_match('/^# Release: (\S+)$/m', $dictionary, $match) === 1 ? $match[1] : null;
        if ($release !== self::RELEASE) {
            throw new UnexpectedValueException('The dictionary is not release ' . self::RELEASE . '.');
        }

        $entries = [];
        $ais = [];
        foreach (\explode("\n", $dictionary) as $number => $line) {
            $line = \rtrim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            try {
                $entry = $this->parseLine($line);
                foreach ($entry[0] as $ai) {
                    if (\in_array($ai, $ais, true)) {
                        throw new UnexpectedValueException('AI ' . $ai . ' is defined twice.');
                    }

                    $ais[] = $ai;
                }
            } catch (UnexpectedValueException $e) {
                throw new UnexpectedValueException(\sprintf('Line %d: %s', $number + 1, $e->getMessage()), 0, $e);
            }

            $entries[] = $entry;
        }

        $this->assertPrefixFree($ais);

        return $this->render($entries, $ais);
    }

    /**
     * One dictionary line: its AIs (a range expanded) and their common specification.
     *
     * @return array{non-empty-list<non-empty-string>, SpecTuple}
     */
    private function parseLine(string $line): array
    {
        $title = null;
        $hash = \strpos($line, '#');
        if ($hash !== false) {
            $title = \trim(\substr($line, $hash + 1));
            $title = $title === '' ? null : $title;
            $line = \substr($line, 0, $hash);
        }

        $tokens = \preg_split('/\s+/', \trim($line), -1, \PREG_SPLIT_NO_EMPTY);
        if ($tokens === false || \count($tokens) < 2) {
            throw new UnexpectedValueException('An entry needs an AI and a specification.');
        }

        $ais = $this->expandAis(\array_shift($tokens));

        $flags = '';
        if (\preg_match('/^[*!?"$%&\'()+,\-.\/:;<=>@\[\\\\\]^_`{|}~]+$/D', $tokens[0]) === 1) {
            $flags = \array_shift($tokens);
        }

        if (\trim($flags, '*?') !== '') {
            throw new UnexpectedValueException('Unknown flag in "' . $flags . '".');
        }

        $components = [];
        while ($tokens !== [] && \preg_match(self::COMPONENT, $tokens[0]) === 1) {
            $components[] = $this->parseComponent(\array_shift($tokens), $components);
        }

        if ($components === []) {
            throw new UnexpectedValueException('An entry needs at least one component.');
        }

        [$requires, $excludes, $primaryKey] = $this->parseAttributes($tokens);

        $spec = [
            $title,
            !\str_contains($flags, '*'),
            \str_contains($flags, '?'),
            $components,
            $requires,
            $excludes,
            $primaryKey,
        ];

        return [$ais, $spec];
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    private function expandAis(string $token): array
    {
        if (\preg_match('/^([0-9]{2,4})(?:-([0-9]{2,4}))?$/D', $token, $match) !== 1) {
            throw new UnexpectedValueException('Bad AI "' . $token . '".');
        }

        $first = $match[1];
        $last = $match[2] ?? $first;
        if (\strlen($last) !== \strlen($first) || $last < $first) {
            throw new UnexpectedValueException('Bad AI range "' . $token . '".');
        }

        return \array_map(
            static fn (int $ai): string => \str_pad((string) $ai, \strlen($first), '0', \STR_PAD_LEFT),
            \range((int) $first, (int) $last),
        );
    }

    /**
     * @param list<ComponentTuple> $previous
     *
     * @return ComponentTuple
     */
    private function parseComponent(string $token, array $previous): array
    {
        \preg_match(self::COMPONENT, $token, $match);
        $optional = ($match[1] ?? '') === '[';
        if ($optional !== (($match[5] ?? '') === ']')) {
            throw new UnexpectedValueException('Unbalanced brackets in "' . $token . '".');
        }

        $last = $previous === [] ? null : $previous[\array_key_last($previous)];
        if ($last !== null && ($last[1] !== $last[2] || ($last[3] && !$optional))) {
            throw new UnexpectedValueException('Only the last component may vary, and optional ones come last.');
        }

        /** @var 'N'|'X'|'Y'|'Z' $charset */
        $charset = $match[2] ?? '';
        $fixed = (int) ($match[3] ?? 0);
        $max = $fixed > 0 ? $fixed : (int) ($match[4] ?? 0);
        $linters = \array_values(\array_filter(\explode(',', $match[6] ?? ''), static fn (string $l): bool => $l !== ''));

        return [$charset, $fixed > 0 ? $fixed : 1, \max(1, $max), $optional, $linters];
    }

    /**
     * @param list<string> $tokens
     *
     * @return array{
     *     list<non-empty-list<non-empty-list<non-empty-string>>>,
     *     list<non-empty-string>,
     *     list<non-empty-list<non-empty-string>>|null,
     * }
     */
    private function parseAttributes(array $tokens): array
    {
        $requires = [];
        $excludes = [];
        $primaryKey = null;
        foreach ($tokens as $token) {
            [$key, $value] = \str_contains($token, '=') ? \explode('=', $token, 2) : [$token, null];
            switch ($key) {
                case 'req':
                    $requires[] = \array_map(
                        fn (string $alternative): array => $this->aiList(\explode('+', $alternative)),
                        $this->aiList(\explode(',', $value ?? '')),
                    );
                    break;
                case 'ex':
                    \array_push($excludes, ...$this->aiList(\explode(',', $value ?? '')));
                    break;
                case 'dlpkey':
                    if ($primaryKey !== null) {
                        throw new UnexpectedValueException('dlpkey given twice.');
                    }

                    $primaryKey = $value === null ? [] : \array_map(
                        fn (string $sequence): array => $this->aiList(\explode(',', $sequence)),
                        \explode('|', $value),
                    );
                    break;
                default:
                    throw new UnexpectedValueException('Unknown attribute "' . $token . '".');
            }
        }

        return [$requires, $excludes, $primaryKey];
    }

    /**
     * Validates AIs and patterns; with `+` joined alternatives still inside, checks each part.
     *
     * @param list<string> $items
     *
     * @return non-empty-list<non-empty-string>
     */
    private function aiList(array $items): array
    {
        $list = [];
        foreach ($items as $item) {
            if ($item === '') {
                throw new UnexpectedValueException('Bad AI or pattern "".');
            }

            foreach (\explode('+', $item) as $part) {
                if (\preg_match(self::AI_PATTERN, $part) !== 1) {
                    throw new UnexpectedValueException('Bad AI or pattern "' . $item . '".');
                }
            }

            $list[] = $item;
        }

        if ($list === []) {
            throw new UnexpectedValueException('Empty AI list.');
        }

        return $list;
    }

    /**
     * GS1 never issues an AI that begins with another AI, which is what lets a reader find where one ends.
     *
     * @param list<string> $ais
     */
    private function assertPrefixFree(array $ais): void
    {
        foreach ($ais as $ai) {
            foreach ($ais as $other) {
                if ($ai !== $other && \str_starts_with($other, $ai)) {
                    throw new UnexpectedValueException(\sprintf('AI %s is a prefix of AI %s.', $ai, $other));
                }
            }
        }
    }

    /**
     * @param list<array{non-empty-list<non-empty-string>, SpecTuple}> $entries
     * @param list<non-empty-string> $ais
     */
    private function render(array $entries, array $ais): string
    {
        $arms = '';
        foreach ($entries as [$entryAis, $spec]) {
            $components = \array_map(
                fn (array $c): string => \sprintf(
                    'new AiComponent(AiCharset::%s, %d, %d, %s, %s)',
                    self::CHARSETS[$c[0]],
                    $c[1],
                    $c[2],
                    $this->export($c[3]),
                    $this->export($c[4]),
                ),
                $spec[3],
            );
            $arms .= \sprintf(
                "            %s => new AiSpec(\$ai, %s, [%s], %s, %s, %s, %s, %s),\n",
                \implode(', ', \array_map($this->export(...), $entryAis)),
                $this->export($spec[0]),
                \implode(', ', $components),
                $this->export($spec[1]),
                $this->export($spec[4]),
                $this->export($spec[5]),
                $this->export($spec[2]),
                $this->export($spec[6]),
            );
        }

        $list = '';
        foreach (\array_chunk($ais, 12) as $chunk) {
            $list .= '        ' . \implode(', ', \array_map($this->export(...), $chunk)) . ",\n";
        }

        $template = <<<'PHP_WRAP'
            <?php

            declare(strict_types=1);

            namespace MSSTC4PHP\ValueObject\Gs1\Internal;

            /**
             * @internal
             *
             * The GS1 Application Identifiers: data format, FNC1 requirement, linters and association rules.
             *
             * Generated by tools/generate-ai-table.php from the GS1 Barcode Syntax Dictionary, release %1$s
             * (https://github.com/gs1/gs1-syntax-dictionary, commit %2$s), resources/gs1-syntax-dictionary.txt,
             * Apache License 2.0 (see NOTICE). Do not edit by hand: update the dictionary and regenerate.
             *
             * @see https://ref.gs1.org/tools/gs1-barcode-syntax-resource/ GS1 Barcode Syntax Resource
             */
            final class AiTable
            {
                public const string RELEASE = '%1$s';

                public const string COMMIT = '%2$s';

                /** @var non-empty-list<non-empty-string> in dictionary order */
                private const array AIS = [
            %3$s    ];

                /**
                 * @infection-ignore-all generated data, guarded byte for byte by AiTableUpToDateTest
                 */
                public static function spec(string $ai): ?AiSpec
                {
                    return match ($ai) {
            %4$s            default => null,
                    };
                }

                /**
                 * The AI that `$data` starts with. AIs are two to four digits and none begins with another.
                 */
                public static function matchPrefix(string $data): ?AiSpec
                {
                    for ($length = 2; $length <= 4; $length++) {
                        $spec = self::spec(\substr($data, 0, $length));
                        if ($spec instanceof AiSpec) {
                            return $spec;
                        }
                    }

                    return null;
                }

                /**
                 * @return non-empty-list<non-empty-string>
                 */
                public static function ais(): array
                {
                    return self::AIS;
                }
            }

            PHP_WRAP;

        return \sprintf($template, self::RELEASE, self::COMMIT, $list, $arms);
    }

    private function export(mixed $value): string
    {
        return match (true) {
            \is_array($value) => '[' . \implode(', ', \array_map($this->export(...), $value)) . ']',
            \is_string($value) => "'" . \addcslashes($value, "'\\") . "'",
            \is_int($value) => (string) $value,
            \is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => throw new UnexpectedValueException('Cannot export a value of type ' . \get_debug_type($value) . '.'),
        };
    }
}
