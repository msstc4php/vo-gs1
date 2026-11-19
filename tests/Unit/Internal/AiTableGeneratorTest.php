<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Gs1\Tests\Support\AiTableGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

/**
 * A dictionary line the generator does not understand must stop it, never be skipped.
 */
#[CoversNothing]
final class AiTableGeneratorTest extends TestCase
{
    private const string HEADER = "# Release: 2026-01-27\n";

    public function testRendersEntries(): void
    {
        $table = new AiTableGenerator()->generate(self::HEADER . "01  *?  N14,csum  dlpkey  # GTIN\n10   ?  X..20  req=01+21,02\n");

        self::assertStringContainsString(
            "'01' => new AiSpec(\$ai, 'GTIN', [new AiComponent(AiCharset::Numeric, 14, 14, false, ['csum'])], false, [], [], true, []),",
            $table,
        );
        self::assertStringContainsString(
            "'10' => new AiSpec(\$ai, null, [new AiComponent(AiCharset::Cset82, 1, 20, false, [])], true, [[['01', '21'], ['02']]], [], true, null),",
            $table,
        );
        self::assertStringContainsString("        '01', '10',\n", $table);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function brokenDictionaries(): iterable
    {
        yield 'other release' => ["# Release: UNSET\n01 * N14\n", 'The dictionary is not release 2026-01-27.'];
        yield 'unknown flag' => [self::HEADER . "01 *! N14\n", 'Line 2: Unknown flag in "*!".'];
        yield 'unknown attribute' => [self::HEADER . "01 * N14 foo=1\n", 'Line 2: Unknown attribute "foo=1".'];
        yield 'bad component' => [self::HEADER . "01 * N14 W2\n", 'Line 2: Unknown attribute "W2".'];
        yield 'no component' => [self::HEADER . "01 * req=02\n", 'Line 2: An entry needs at least one component.'];
        yield 'bad AI' => [self::HEADER . "1 * N14\n", 'Line 2: Bad AI "1".'];
        yield 'bad range' => [self::HEADER . "39-310 N6\n", 'Line 2: Bad AI range "39-310".'];
        yield 'reversed range' => [self::HEADER . "99-91 N6\n", 'Line 2: Bad AI range "99-91".'];
        yield 'variable component before another' => [
            self::HEADER . "01 X..2 N1\n",
            'Line 2: Only the last component may vary, and optional ones come last.',
        ];
        yield 'mandatory after optional' => [
            self::HEADER . "01 [N1] N1\n",
            'Line 2: Only the last component may vary, and optional ones come last.',
        ];
        yield 'unbalanced bracket' => [self::HEADER . "01 [N1\n", 'Line 2: Unbalanced brackets in "[N1".'];
        yield 'bad pattern' => [self::HEADER . "01 N1 req=0x\n", 'Line 2: Bad AI or pattern "0x".'];
        yield 'empty requirement' => [self::HEADER . "01 N1 req=\n", 'Line 2: Bad AI or pattern "".'];
        yield 'dlpkey twice' => [self::HEADER . "01 N1 dlpkey dlpkey\n", 'Line 2: dlpkey given twice.'];
        yield 'defined twice' => [self::HEADER . "01 N1\n01 N1\n", 'Line 3: AI 01 is defined twice.'];
        yield 'not prefix-free' => [self::HEADER . "01 N1\n012 N1\n", 'AI 01 is a prefix of AI 012.'];
    }

    #[DataProvider('brokenDictionaries')]
    public function testRefusesWhatItDoesNotUnderstand(string $dictionary, string $message): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($message);

        new AiTableGenerator()->generate($dictionary);
    }
}
