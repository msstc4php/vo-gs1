<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiLinters::class)]
#[CoversClass(Gs1Dates::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(Gs1CheckDigit::class)]
#[UsesClass(GmnCheckCharacters::class)]
final class AiLintersTest extends TestCase
{
    private const string SOURCES = __DIR__ . '/../../../docs/sources/GS1.md';

    /**
     * Every linter the dictionary names is either implemented or listed as not enforced, with a reason, in GS1.md.
     */
    public function testEveryDictionaryLinterIsImplementedOrRecordedAsNotEnforced(): void
    {
        $sources = (string) \file_get_contents(self::SOURCES);
        $named = [];
        foreach (AiTable::ais() as $ai) {
            foreach (AiTable::spec($ai)->components ?? [] as $component) {
                foreach ($component->linters as $linter) {
                    $named[$linter] = true;
                }
            }
        }

        self::assertNotEmpty($named);
        foreach (\array_keys($named) as $linter) {
            $implemented = \in_array($linter, AiLinters::IMPLEMENTED, true);
            $unenforced = \array_key_exists($linter, AiLinters::NOT_ENFORCED);
            self::assertTrue($implemented xor $unenforced, \sprintf('Linter %s must be implemented or not enforced.', $linter));
            self::assertMatchesRegularExpression(
                $implemented
                    ? '/^\| `' . $linter . '` \| enforced \|/m'
                    : '/^\| `' . $linter . '` \| not enforced \| \S/m',
                $sources,
                \sprintf('GS1.md must record linter %s.', $linter),
            );
        }
    }

    public function testCset64(): void
    {
        self::assertTrue(AiLinters::isCset64('AbC-_9'));
        self::assertTrue(AiLinters::isCset64('AbC-_9=='));
        self::assertTrue(AiLinters::isCset64('AbC='));
        self::assertFalse(AiLinters::isCset64('A==='));
        self::assertFalse(AiLinters::isCset64('A=b'));
        self::assertFalse(AiLinters::isCset64('A+b'));
        self::assertFalse(AiLinters::isCset64('A/b'));
    }

    public function testUnenforcedLintersAlwaysPass(): void
    {
        foreach (\array_keys(AiLinters::NOT_ENFORCED) as $linter) {
            self::assertTrue(AiLinters::passes($linter, '!!'));
        }
    }

    public function testUnknownLinterIsAProgrammingError(): void
    {
        $this->expectException(LogicException::class);
        AiLinters::passes('nosuchlinter', '1');
    }

    /**
     * Valid and invalid examples, most of them from the reference implementations' unit tests (`src/lint_*.c`).
     *
     * @return iterable<string, array{string, string, bool}>
     */
    public static function cases(): iterable
    {
        yield 'csum ok' => ['csum', '09506000134352', true];
        yield 'csum bad' => ['csum', '09506000134353', false];
        yield 'csum one digit' => ['csum', '0', false];
        yield 'csumalpha GMN' => ['csumalpha', '1987654Ad4X4bL5ttr2310c2K', true];
        yield 'csumalpha linter vector' => ['csumalpha', '12345678901234567890123NT', true];
        yield 'csumalpha bad pair' => ['csumalpha', '1987654Ad4X4bL5ttr2310c2L', false];
        yield 'csumalpha empty body' => ['csumalpha', '22', true];
        yield 'csumalpha empty body bad' => ['csumalpha', '23', false];
        yield 'csumalpha too short' => ['csumalpha', '2', false];
        yield 'csumalpha body too long' => ['csumalpha', \str_repeat('1', 24) . '22', false];
        yield 'csumalpha body outside CSET 82' => ['csumalpha', '#22', false];
        yield 'gcppos2 empty' => ['gcppos2', '', false];
        yield 'yymmd0 day zero' => ['yymmd0', '250200', true];
        yield 'yymmd0 month zero' => ['yymmd0', '250001', false];
        yield 'yymmd0 month 12' => ['yymmd0', '251231', true];
        yield 'yymmd0 month 13' => ['yymmd0', '251301', false];
        yield 'yymmd0 Feb 29 leap' => ['yymmd0', '240229', true];
        yield 'yymmd0 Feb 29 non-leap' => ['yymmd0', '230229', false];
        yield 'yymmd0 Apr 31' => ['yymmd0', '250431', false];
        yield 'yymmd0 Apr 30' => ['yymmd0', '250430', true];
        yield 'yymmd0 Jan 32' => ['yymmd0', '250132', false];
        yield 'yymmd0 Feb 29 of YY 00' => ['yymmd0', '000229', true];
        yield 'yymmd0 Feb 29 of YY 01' => ['yymmd0', '010229', false];
        yield 'yymmdd Feb 29 of YY 96' => ['yymmdd', '960229', true];
        yield 'yymmd0 short' => ['yymmd0', '25013', false];
        yield 'yymmd0 non-digit' => ['yymmd0', '2501A1', false];
        yield 'yymmdd day zero' => ['yymmdd', '250200', false];
        yield 'yymmdd ok' => ['yymmdd', '250228', true];
        yield 'yymmdd bad month' => ['yymmdd', '251301', false];
        yield 'yyyymmdd ok' => ['yyyymmdd', '20240229', true];
        yield 'yyyymmdd 1900 not leap' => ['yyyymmdd', '19000229', false];
        yield 'yyyymmdd 2000 leap' => ['yyyymmdd', '20000229', true];
        yield 'yyyymmdd day zero' => ['yyyymmdd', '20240200', false];
        yield 'yyyymmdd short' => ['yyyymmdd', '2024020', false];
        yield 'hh 23' => ['hh', '23', true];
        yield 'hh 24' => ['hh', '24', false];
        yield 'hh short' => ['hh', '2', false];
        yield 'mi 59' => ['mi', '59', true];
        yield 'mi 60' => ['mi', '60', false];
        yield 'ss 59' => ['ss', '59', true];
        yield 'ss 60' => ['ss', '60', false];
        yield 'ss non-digit' => ['ss', '5a', false];
        yield 'hhmi ok' => ['hhmi', '2359', true];
        yield 'hhmi bad hour' => ['hhmi', '2400', false];
        yield 'hhmi bad minute' => ['hhmi', '2360', false];
        yield 'hhmi short' => ['hhmi', '235', false];
        yield 'iso3166 RU' => ['iso3166', '643', true];
        yield 'iso3166 unknown' => ['iso3166', '999', false];
        yield 'iso3166 short' => ['iso3166', '64', false];
        yield 'iso3166 non-digit' => ['iso3166', '6a3', false];
        yield 'iso3166999 999' => ['iso3166999', '999', true];
        yield 'iso3166999 RU' => ['iso3166999', '643', true];
        yield 'iso3166999 unknown' => ['iso3166999', '998', false];
        yield 'iso3166alpha2 RU' => ['iso3166alpha2', 'RU', true];
        yield 'iso3166alpha2 lower case' => ['iso3166alpha2', 'ru', false];
        yield 'iso3166alpha2 unknown' => ['iso3166alpha2', 'ZZ', false];
        yield 'iso4217 RUB' => ['iso4217', '643', true];
        yield 'iso4217 XDR' => ['iso4217', '960', true];
        yield 'iso4217 XXX' => ['iso4217', '999', true];
        yield 'iso4217 XTS' => ['iso4217', '963', true];
        yield 'iso4217 XBA' => ['iso4217', '955', true];
        yield 'iso4217 XSU' => ['iso4217', '994', true];
        yield 'iso4217 unknown' => ['iso4217', '000', false];
        yield 'iso4217 short' => ['iso4217', '64', false];
        yield 'iso4217 non-digit' => ['iso4217', '6a3', false];
        yield 'iso5218 2' => ['iso5218', '2', true];
        yield 'iso5218 9' => ['iso5218', '9', true];
        yield 'iso5218 3' => ['iso5218', '3', false];
        yield 'zero' => ['zero', '0', true];
        yield 'zero not' => ['zero', '1', false];
        yield 'nonzero' => ['nonzero', '0010', true];
        yield 'nonzero not' => ['nonzero', '0000', false];
        yield 'nozeroprefix' => ['nozeroprefix', '10', true];
        yield 'nozeroprefix not' => ['nozeroprefix', '01', false];
        yield 'yesno 1' => ['yesno', '1', true];
        yield 'yesno 0' => ['yesno', '0', true];
        yield 'yesno 2' => ['yesno', '2', false];
        yield 'yesno long' => ['yesno', '10', false];
        yield 'winding 9' => ['winding', '9', true];
        yield 'winding 0' => ['winding', '0', true];
        yield 'winding 1' => ['winding', '1', true];
        yield 'winding 2' => ['winding', '2', false];
        yield 'pieceoftotal 0102' => ['pieceoftotal', '0102', true];
        yield 'pieceoftotal 0202' => ['pieceoftotal', '0202', true];
        yield 'pieceoftotal 0302' => ['pieceoftotal', '0302', false];
        yield 'pieceoftotal 0002' => ['pieceoftotal', '0002', false];
        yield 'pieceoftotal 0100' => ['pieceoftotal', '0100', false];
        yield 'pieceoftotal odd' => ['pieceoftotal', '010', false];
        yield 'hasnondigit' => ['hasnondigit', '12A', true];
        yield 'hasnondigit not' => ['hasnondigit', '123', false];
        yield 'posinseqslash 1/2' => ['posinseqslash', '1/2', true];
        yield 'posinseqslash 2/2' => ['posinseqslash', '2/2', true];
        yield 'posinseqslash 3/2' => ['posinseqslash', '3/2', false];
        yield 'posinseqslash 9/10' => ['posinseqslash', '9/10', true];
        yield 'posinseqslash 10/9' => ['posinseqslash', '10/9', false];
        yield 'posinseqslash 12/13' => ['posinseqslash', '12/13', true];
        yield 'posinseqslash 13/12' => ['posinseqslash', '13/12', false];
        yield 'posinseqslash 0/2' => ['posinseqslash', '0/2', false];
        yield 'posinseqslash 1/0' => ['posinseqslash', '1/02', false];
        yield 'posinseqslash no slash' => ['posinseqslash', '12', false];
        yield 'posinseqslash no end' => ['posinseqslash', '1/', false];
        yield 'posinseqslash no position' => ['posinseqslash', '/1', false];
        yield 'posinseqslash letter' => ['posinseqslash', '1/A', false];
        yield 'latitude max' => ['latitude', '1800000000', true];
        yield 'latitude over' => ['latitude', '1800000001', false];
        yield 'latitude short' => ['latitude', '180000000', false];
        yield 'longitude max' => ['longitude', '3600000000', true];
        yield 'longitude over' => ['longitude', '3600000001', false];
        yield 'hyphen' => ['hyphen', '--', true];
        yield 'hyphen not' => ['hyphen', '-a', false];
        yield 'importeridx letter' => ['importeridx', 'a', true];
        yield 'importeridx hyphen' => ['importeridx', '-', true];
        yield 'importeridx underscore' => ['importeridx', '_', true];
        yield 'importeridx dot' => ['importeridx', '.', false];
        yield 'importeridx two' => ['importeridx', 'ab', false];
        yield 'pcenc ok' => ['pcenc', 'A%20B', true];
        yield 'pcenc plain' => ['pcenc', 'AB', true];
        yield 'pcenc truncated' => ['pcenc', 'A%2', false];
        yield 'pcenc not hex' => ['pcenc', 'A%2G', false];
        yield 'pcenc percent at the end' => ['pcenc', 'AB%', false];
        yield 'pcenc two encodings' => ['pcenc', '%41%42', true];
        yield 'gcppos1 ok' => ['gcppos1', '0952', true];
        yield 'gcppos1 letter' => ['gcppos1', '095X1', false];
        yield 'gcppos1 short' => ['gcppos1', '095', false];
        yield 'gcppos2 ok' => ['gcppos2', 'X0952', true];
        yield 'gcppos2 letter' => ['gcppos2', '0095X', false];
    }

    #[DataProvider('cases')]
    public function testLinter(string $linter, string $data, bool $expected): void
    {
        self::assertSame($expected, AiLinters::passes($linter, $data));
    }
}
