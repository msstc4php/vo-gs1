<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Marking\EU;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidMarkingCodeException;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Charsets;
use MSSTC4PHP\ValueObject\Gs1\Internal\Gs1Dates;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TobaccoUnitIdentifier::class)]
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
        yield 'too long' => [\str_repeat('A', 43) . '26111923', 'Tobacco unit level UI must have 11 to 50 alphanumeric characters, got 51.'];
        yield 'too short' => ['AB19052014', 'Tobacco unit level UI must have 11 to 50 alphanumeric characters, got 10.'];
        yield 'not alphanumeric' => ['AB-1234519052014', 'Tobacco unit level UI must have 11 to 50 alphanumeric characters, got 16.'];
        yield 'trailing newline' => ["ABC19052014\n", 'Tobacco unit level UI must have 11 to 50 alphanumeric characters, got 12.'];
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
}
