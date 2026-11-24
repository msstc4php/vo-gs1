<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Udi;

use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc;
use MSSTC4PHP\ValueObject\Gs1\Udi\NdcConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ndc::class)]
#[UsesClass(NdcConfiguration::class)]
final class NdcBillingFormatTest extends TestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function billingForms(): iterable
    {
        // 91 FR 10749, Table 1: 10010-001-01 → 10010000101; the package code 01 also starts with a zero.
        yield 'FDA Table 1 is ambiguous' => ['10010000101', ['10010-001-01', '10010-0001-1']];
        yield 'three candidates' => ['01234012301', ['1234-0123-01', '01234-123-01', '01234-0123-1']];
        yield 'only the labeler is padded' => ['01234567891', ['1234-5678-91']];
        yield 'only the product is padded' => ['12345067891', ['12345-678-91']];
        yield 'only the package is padded' => ['12345678901', ['12345-6789-1']];
        yield 'no segment starts with zero' => ['12345678910', []];
        yield 'all zeros' => ['00000000000', ['0000-0000-00', '00000-000-00', '00000-0000-0']];
        yield 'ten digits' => ['1234567890', []];
        yield 'twelve digits' => ['012345678901', []];
        yield 'hyphenated' => ['12345-678-9', []];
        yield 'letter' => ['1234506789A', []];
        yield 'leading letter' => ['A0123456789', []];
        yield 'empty' => ['', []];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('billingForms')]
    public function testCandidatesFromBillingFormat(string $billing, array $expected): void
    {
        $candidates = Ndc::candidatesFromBillingFormat($billing);

        self::assertSame($expected, \array_map(static fn (Ndc $ndc): string => (string) $ndc, $candidates));
        foreach ($candidates as $candidate) {
            self::assertSame($billing, $candidate->toBillingFormat());
        }
    }

    public function testEveryTenDigitNdcIsAmongTheCandidatesOfItsBillingForm(): void
    {
        foreach (['0002-1433-80', '50580-0600-2', '10010-001-01', '99999-123-45', '0001-0001-01'] as $value) {
            $ndc = Ndc::from($value);
            $billing = $ndc->toBillingFormat();
            self::assertNotNull($billing);
            self::assertContains($value, \array_map(static fn (Ndc $candidate): string => (string) $candidate, Ndc::candidatesFromBillingFormat($billing)));
        }
    }
}
