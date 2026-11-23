<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Marking\EU;

use DateTimeImmutable;
use DateTimeZone;
use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifier;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifierWithoutTimeStamp;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TobaccoUnitIdentifier::class)]
#[CoversClass(TobaccoUnitIdentifierWithoutTimeStamp::class)]
#[UsesClass(Gs1Dates::class)]
#[UsesClass(Gs1Charsets::class)]
final class TobaccoUnitIdentifierTest extends TestCase
{
    public function testTheTimeStampIsTheLastEightCharactersInUtc(): void
    {
        $identifier = TobaccoUnitIdentifier::from('AB1x9ZqkP3m719052014');

        self::assertSame('AB1x9ZqkP3m719052014', (string) $identifier);
        self::assertSame('AB1x9ZqkP3m7', $identifier->issuerPart());
        self::assertSame('19052014', $identifier->timeStamp());
        self::assertSame('2019-05-20T14:00:00+00:00', $identifier->manufacturedAt()->format(DATE_ATOM));
        self::assertSame('UTC', $identifier->manufacturedAt()->getTimezone()->getName());
    }

    public function testFiftyCharactersIsTheMaximum(): void
    {
        $value = \str_repeat('A', 42) . '26111923';

        self::assertSame($value, (string) TobaccoUnitIdentifier::from($value));
        self::assertSame('2026-11-19T23:00:00+00:00', TobaccoUnitIdentifier::from($value)->manufacturedAt()->format(DATE_ATOM));
        self::assertSame('2024-02-29T00:00:00+00:00', TobaccoUnitIdentifier::from('ABC24022900')->manufacturedAt()->format(DATE_ATOM));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalid(): iterable
    {
        yield 'too long' => [\str_repeat('A', 43) . '26111923', 'Tobacco unit level UI must have 11 to 50 alphanumeric characters.'];
        yield 'too short' => ['AB19052014', 'Tobacco unit level UI must have 11 to 50 alphanumeric characters.'];
        yield 'not alphanumeric' => ['AB-1234519052014', 'Tobacco unit level UI must have 11 to 50 alphanumeric characters.'];
        yield 'trailing newline' => ["ABC19052014\n", 'Tobacco unit level UI must have 11 to 50 alphanumeric characters.'];
        yield 'hour 24' => ['ABC19052024', 'Tobacco unit level UI must end with the time stamp YYMMDDhh.'];
        yield 'month 13' => ['ABC19132014', 'Tobacco unit level UI must end with the time stamp YYMMDDhh.'];
        yield 'day 00' => ['ABC19050014', 'Tobacco unit level UI must end with the time stamp YYMMDDhh.'];
        yield '29 February of 2023' => ['ABC23022914', 'Tobacco unit level UI must end with the time stamp YYMMDDhh.'];
        yield 'letters in the time stamp' => ['ABC190520AB', 'Tobacco unit level UI must end with the time stamp YYMMDDhh.'];
    }

    #[DataProvider('invalid')]
    public function testInvalidIdentifiersAreRefused(string $value, string $message): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage($message);

        TobaccoUnitIdentifier::from($value);
    }

    public function testCarrierContentWithoutTimeStampIsNeverReadAsATimeStamp(): void
    {
        // Article 21(4): the time stamp may be printed separately; trailing digits stay part of the issuer's code.
        $content = TobaccoUnitIdentifierWithoutTimeStamp::from('AB1x9ZqkP3m719052014');

        self::assertSame('AB1x9ZqkP3m719052014', (string) $content);
        self::assertSame('ABC', (string) TobaccoUnitIdentifierWithoutTimeStamp::from('ABC'));
        self::assertSame(\str_repeat('A', 42), (string) TobaccoUnitIdentifierWithoutTimeStamp::from(\str_repeat('A', 42)));
    }

    public function testJoiningAndSplittingTheTimeStamp(): void
    {
        $content = TobaccoUnitIdentifierWithoutTimeStamp::from('AB1x9ZqkP3m7');
        $full = $content->withTimeStamp(new DateTimeImmutable('2019-05-20 17:30:00', new DateTimeZone('Europe/Moscow')));

        self::assertSame('AB1x9ZqkP3m719052014', (string) $full);
        self::assertTrue($full->withoutTimeStamp()->equals($content));
        self::assertSame('ABC00010100', (string) TobaccoUnitIdentifierWithoutTimeStamp::from('ABC')->withTimeStamp(new DateTimeImmutable('2000-01-01 00:00:00', new DateTimeZone('UTC'))));
        self::assertSame('ABC99123123', (string) TobaccoUnitIdentifierWithoutTimeStamp::from('ABC')->withTimeStamp(new DateTimeImmutable('2099-12-31 23:00:00', new DateTimeZone('UTC'))));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidWithoutTimeStamp(): iterable
    {
        yield 'too short' => ['AB', 'Tobacco unit level UI without time stamp must have 3 to 42 alphanumeric characters.'];
        yield 'too long' => [\str_repeat('A', 43), 'Tobacco unit level UI without time stamp must have 3 to 42 alphanumeric characters.'];
        yield 'not alphanumeric' => ['AB-C', 'Tobacco unit level UI without time stamp must have 3 to 42 alphanumeric characters.'];
        yield 'trailing newline' => ["ABC\n", 'Tobacco unit level UI without time stamp must have 3 to 42 alphanumeric characters.'];
    }

    #[DataProvider('invalidWithoutTimeStamp')]
    public function testInvalidCarrierContentIsRefused(string $value, string $message): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage($message);

        TobaccoUnitIdentifierWithoutTimeStamp::from($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outOfCentury(): iterable
    {
        yield '1999' => ['1999-12-31 23:00:00'];
        yield '2100' => ['2100-01-01 00:00:00'];
    }

    #[DataProvider('outOfCentury')]
    public function testATimeStampOutsideTheCenturyIsRefused(string $at): void
    {
        $this->expectException(InvalidMarkingCodeException::class);
        $this->expectExceptionMessage('Tobacco unit level UI time stamp must be in 2000-2099 (UTC).');

        TobaccoUnitIdentifierWithoutTimeStamp::from('ABC')->withTimeStamp(new DateTimeImmutable($at, new DateTimeZone('UTC')));
    }
}
