<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\ElementString;

use MSSTC4PHP\ValueObject\Finance\Iban;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiCharset;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiComponent;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiElement;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiLinters;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiSpec;
use MSSTC4PHP\ValueObject\Gs1\Internal\AiTable;
use MSSTC4PHP\ValueObject\Gs1\Internal\ElementStringSyntax;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1CheckDigit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ElementString::class)]
#[CoversClass(ElementStringSyntax::class)]
#[CoversClass(AiLinters::class)]
#[UsesClass(AiTable::class)]
#[UsesClass(AiSpec::class)]
#[UsesClass(AiComponent::class)]
#[UsesClass(AiElement::class)]
#[UsesClass(Gs1CheckDigit::class)]
final class LinterReportTest extends TestCase
{
    /**
     * The unit test vectors of `lint_iban.c` at commit 58c1dc85d9ff4eb9368293bcf5264a9fe70bfc72, the ones AI (8007)
     * can carry (1 to 34 characters of CSET 82).
     *
     * @return iterable<string, array{string, bool}>
     */
    public static function ibans(): iterable
    {
        foreach ([
            'FR7630006000011234567890189', 'DE91100000000123456789', 'GR9608100010000001234567890',
            'MU43BOMM0101123456789101000MUR', 'PK70BANK0000123456789000', 'PL10105000997603123456789123',
            'RO09BCYP0000001234567890', 'SA4420000001234567891234', 'ES7921000813610123456789', 'CH5604835012345678009',
            'GB98MIDL07009312345678', 'BE71096123456769', 'LC14BOSL123456789012345678901234',
            'MT90MALT011000012345MTLCAST001SABC',
        ] as $pass) {
            yield $pass => [$pass, true];
        }

        foreach ([
            'BE71096123456760', 'LC14BOSL123456789012345678901230', 'MT90MALT011000012345MTLCAST001SAB0', 'B', 'BE',
            'BE7', 'BE71', 'LC14BOSLxx3456789012345678901230', 'XX361234567890',
        ] as $fail) {
            yield $fail => [$fail, false];
        }

        yield 'ten characters with a valid check are too short' => ['BE50123456', false];
        yield 'eleven characters are enough' => ['BE191234567', true];
        yield 'letters in the BBAN' => ['GB49ZZZZ07009312345678', true];
        yield 'lowercase country' => ['fr7630006000011234567890189', false];
    }

    #[DataProvider('ibans')]
    public function testIbanFollowsLintIban(string $data, bool $expected): void
    {
        self::assertSame($expected, AiLinters::passesReported('iban', $data));
        self::assertSame($expected, ElementString::from('(8007)' . $data)->hasValidLinterData());
        self::assertTrue(AiLinters::passes('iban', $data), 'never enforced in 1.x');
    }

    public function testTheMod97CheckMatchesVoBaseIban(): void
    {
        foreach (['FR7630006000011234567890189', 'DE91100000000123456789', 'GB98MIDL07009312345678', 'BE71096123456760', 'GB99MIDL07009312345678'] as $iban) {
            self::assertSame(Iban::isValid($iban), AiLinters::passesReported('iban', $iban), $iban);
        }
    }

    /**
     * `lint_mediatype.c` passes 01-10 and 80-99 and fails 00 and 11-79 (GenSpecs 26.0, Table 3-11).
     *
     * @return iterable<string, array{string, bool}>
     */
    public static function mediaTypes(): iterable
    {
        for ($value = 0; $value <= 99; $value++) {
            $data = \sprintf('%02d', $value);
            yield $data => [$data, ($value >= 1 && $value <= 10) || $value >= 80];
        }
    }

    #[DataProvider('mediaTypes')]
    public function testMediaTypeFollowsLintMediatype(string $data, bool $expected): void
    {
        self::assertSame($expected, AiLinters::passesReported('mediatype', $data));
        self::assertSame($expected, ElementString::from('(7241)' . $data)->hasValidLinterData());
        self::assertTrue(AiLinters::passes('mediatype', $data), 'never enforced in 1.x');
    }

    public function testIbanOutsideAi8007FailsTheLinter(): void
    {
        self::assertFalse(AiLinters::passesReported('iban', 'FR76 30006000011234567890189'));
        self::assertFalse(AiLinters::passesReported('iban', 'MT87MALT011000012345MTLCAST001SABCZ'), '35 characters with a valid check');
    }

    public function testMediaTypeNeedsTwoDigits(): void
    {
        foreach (['', '0', '1', '001', '010', 'AA', '_1', '8A'] as $data) {
            self::assertFalse(AiLinters::passesReported('mediatype', $data), $data);
        }
    }

    public function testOtherLintersAreNotReported(): void
    {
        self::assertTrue(AiLinters::passesReported('packagetype', 'ZZZZ'));
        self::assertTrue(AiLinters::passesReported('csum', '1'));
        foreach (AiLinters::REPORTED as $linter) {
            self::assertArrayHasKey($linter, AiLinters::NOT_ENFORCED);
        }
    }

    public function testEveryElementIsChecked(): void
    {
        self::assertTrue(ElementString::from('(01)09506000134352(10)ABC')->hasValidLinterData());
        self::assertTrue(ElementString::from('(7041)ZZZZ')->hasValidLinterData());
        self::assertTrue(ElementString::from('(7241)01(8007)DE91100000000123456789')->hasValidLinterData());
        self::assertFalse(ElementString::from('(7241)01(8007)DE91100000000123456780')->hasValidLinterData());
        self::assertFalse(ElementString::from('(8007)DE91100000000123456789(7241)00')->hasValidLinterData());
    }

    public function testComponentsAreReadAtTheirPositionsAndAnOmittedOptionalOneIsSkipped(): void
    {
        $spec = new AiSpec('7241', null, [
            new AiComponent(AiCharset::Numeric, 2, 2, false, ['mediatype']),
            new AiComponent(AiCharset::Numeric, 2, 2, true, ['mediatype']),
        ], true, [], [], false, null);

        self::assertTrue(ElementStringSyntax::passesReportedLinters([new AiElement($spec, '01')]));
        self::assertTrue(ElementStringSyntax::passesReportedLinters([new AiElement($spec, '0180')]));
        self::assertFalse(ElementStringSyntax::passesReportedLinters([new AiElement($spec, '0100')]));
        self::assertFalse(ElementStringSyntax::passesReportedLinters([new AiElement($spec, '0001')]));

        $offset = new AiSpec('7241', null, [
            new AiComponent(AiCharset::Numeric, 4, 4, false, []),
            new AiComponent(AiCharset::Numeric, 2, 2, false, []),
            new AiComponent(AiCharset::Numeric, 2, 2, false, ['mediatype']),
        ], true, [], [], false, null);

        self::assertTrue(ElementStringSyntax::passesReportedLinters([new AiElement($offset, '00009901')]));
        self::assertFalse(ElementStringSyntax::passesReportedLinters([new AiElement($offset, '01019900')]));
    }
}
