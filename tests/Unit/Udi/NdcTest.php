<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Udi;

use MSSTC4PHP\ValueObject\Exception\AmbiguousValueException;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidUdiException;
use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc;
use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc12;
use MSSTC4PHP\ValueObject\Gs1\Udi\NdcConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ndc::class)]
#[CoversClass(Ndc12::class)]
#[CoversClass(NdcConfiguration::class)]
final class NdcTest extends TestCase
{
    /**
     * @return iterable<string, array{string, NdcConfiguration, string, string, string, string|null, string}>
     */
    public static function configurations(): iterable
    {
        // 91 FR 10749, Table 1: 10010-001-01 → 10010-0001-01 → 10010000101.
        yield '5-3-2 (FDA Table 1)' => ['10010-001-01', NdcConfiguration::FiveThreeTwo, '10010', '001', '01', '10010000101', '010010-0001-01'];
        yield '4-4-2' => ['0002-1433-80', NdcConfiguration::FourFourTwo, '0002', '1433', '80', '00002143380', '000002-1433-80'];
        yield '5-4-1' => ['50580-0600-2', NdcConfiguration::FiveFourOne, '50580', '0600', '2', '50580060002', '050580-0600-02'];
        yield '6-3-2 (FDA Table 1)' => ['100100-001-01', NdcConfiguration::SixThreeTwo, '100100', '001', '01', null, '100100-0001-01'];
        yield '6-4-1' => ['123456-7890-1', NdcConfiguration::SixFourOne, '123456', '7890', '1', null, '123456-7890-01'];
    }

    #[DataProvider('configurations')]
    public function testEveryConfigurationOf20733IsAccepted(string $value, NdcConfiguration $configuration, string $labeler, string $product, string $package, ?string $billing, string $ndc12): void
    {
        $ndc = Ndc::from($value);

        self::assertSame($value, (string) $ndc);
        self::assertSame($configuration, $ndc->configuration());
        self::assertSame($labeler, $ndc->labelerCode());
        self::assertSame($product, $ndc->productCode());
        self::assertSame($package, $ndc->packageCode());
        self::assertSame($billing, $ndc->toBillingFormat());
        self::assertSame($ndc12, (string) $ndc->toNdc12());
        self::assertSame($configuration->length(), $configuration->labelerLength() + $configuration->productLength() + $configuration->packageLength());
    }

    public function testABareTenDigitNdcIsAmbiguousAndListsItsConfigurations(): void
    {
        // 91 FR 10749, Table 2: 9999912345 could be 9999-9123-45, 99999-123-45 or 99999-1234-5.
        try {
            Ndc::from('9999912345');
            self::fail('A bare 10-digit NDC must be ambiguous.');
        } catch (AmbiguousValueException $exception) {
            self::assertSame([Ndc::class], $exception->candidates());
            self::assertSame('Ambiguous NDC configuration (10 digits fit 4-4-2, 5-3-2, 5-4-1): the value fits Ndc.', $exception->getMessage());
        }

        self::assertSame(
            [NdcConfiguration::FourFourTwo, NdcConfiguration::FiveThreeTwo, NdcConfiguration::FiveFourOne],
            Ndc::candidates('9999912345'),
        );
        self::assertSame(
            ['9999-9123-45', '99999-123-45', '99999-1234-5'],
            \array_map(static fn (NdcConfiguration $configuration): string => (string) Ndc::fromDigits('9999912345', $configuration), Ndc::candidates('9999912345')),
        );
    }

    public function testABareElevenDigitNdcIsAmbiguous(): void
    {
        self::assertSame([NdcConfiguration::SixThreeTwo, NdcConfiguration::SixFourOne], Ndc::candidates('10010000101'));

        $this->expectException(AmbiguousValueException::class);
        $this->expectExceptionMessage('Ambiguous NDC configuration (11 digits fit 6-3-2, 6-4-1): the value fits Ndc.');

        Ndc::from('10010000101');
    }

    public function testCandidatesOfOtherInput(): void
    {
        self::assertSame([], Ndc::candidates('123456789'));
        self::assertSame([], Ndc::candidates('999991234A'));
        self::assertSame([], Ndc::candidates('9999-91234'));
        self::assertSame([], Ndc::candidates(''));
    }

    public function testFromDigitsRefusesAConfigurationThatDoesNotFit(): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('NDC digits do not fit the 6-4-1 configuration.');

        Ndc::fromDigits('9999912345', NdcConfiguration::SixFourOne);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function badNdcs(): iterable
    {
        yield 'too long' => ['12345-1234-123', 'NDC must have at most 13 characters, got 14.'];
        yield '4-3-2' => ['1234-123-12', 'NDC segment lengths 4-3-2 are not a configuration of 21 CFR 207.33(b)(1)(i) and (ii).'];
        yield '4-4-1' => ['1234-1234-1', 'NDC segment lengths 4-4-1 are not a configuration of 21 CFR 207.33(b)(1)(i) and (ii).'];
        yield '5-4-2 billing form' => ['12345-1234-12', 'NDC segment lengths 5-4-2 are not a configuration of 21 CFR 207.33(b)(1)(i) and (ii).'];
        yield 'letters' => ['1234A-123-12', 'NDC must be three hyphenated digit segments.'];
        yield 'nine digits' => ['123456789', 'NDC must be three hyphenated digit segments.'];
        yield 'two segments' => ['12345-12345', 'NDC must be three hyphenated digit segments.'];
        yield 'trailing newline' => ["1234-1234-12\n", 'NDC must be three hyphenated digit segments.'];
        yield 'empty' => ['', 'NDC must be three hyphenated digit segments.'];
    }

    #[DataProvider('badNdcs')]
    public function testMalformedNdcsAreRefused(string $value, string $message): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage($message);

        Ndc::from($value);
    }

    public function testNdc12AcceptsTheFinalRuleFormat(): void
    {
        $ndc = Ndc12::from('123456-7890-12');

        self::assertSame('123456-7890-12', (string) $ndc);
        self::assertSame('123456', $ndc->labelerCode());
        self::assertSame('7890', $ndc->productCode());
        self::assertSame('12', $ndc->packageCode());
        self::assertTrue($ndc->equals(Ndc12::from('123456789012')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function badNdc12s(): iterable
    {
        yield 'one hyphen' => ['123456-789012'];
        yield 'other hyphen' => ['1234567890-12'];
        yield '5-4-2' => ['12345-7890-12'];
        yield '11 digits' => ['12345678901'];
        yield 'letters' => ['12345A-7890-12'];
        yield 'trailing newline' => ["123456789012\n"];
    }

    #[DataProvider('badNdc12s')]
    public function testNdc12RefusesOtherShapes(string $value): void
    {
        $this->expectException(InvalidUdiException::class);
        $this->expectExceptionMessage('NDC12 must be 12 digits, bare or hyphenated 6-4-2.');

        Ndc12::from($value);
    }
}
