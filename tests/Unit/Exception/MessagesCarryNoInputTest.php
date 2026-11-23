<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Exception;

use Closure;
use MSSTC4PHP\ValueObject\Contract\StringConvertible;
use MSSTC4PHP\ValueObject\Exception\InvalidValueObjectException;
use MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcPureIdentityUri;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcTagUri;
use MSSTC4PHP\ValueObject\Gs1\Key\Giai;
use MSSTC4PHP\ValueObject\Gs1\Key\Gmn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\FmdPackCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\Ppn;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifier;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifierWithoutTimeStamp;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingIdentifier;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\IsbtUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Owner rule: a message carries no part of the input, not even its length.
 */
#[CoversNothing]
final class MessagesCarryNoInputTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<StringConvertible>, string}>
     */
    public static function overLongInputs(): iterable
    {
        yield 'GTIN' => [Gtin::class, \str_repeat('4', 17)];
        yield 'GIAI' => [Giai::class, '0614141' . \str_repeat('A', 32)];
        yield 'GMN' => [Gmn::class, \str_repeat('1', 34)];
        yield 'element string' => [ElementString::class, '(10)' . \str_repeat('A', 14185)];
        yield 'Digital Link' => [DigitalLink::class, 'https://example.com/01/09520123456788?x=' . \str_repeat('a', 14150)];
        yield 'EPC pure identity URI' => [EpcPureIdentityUri::class, 'urn:epc:id:giai:9521141.' . \str_repeat('1', 1077)];
        yield 'EPC Tag URI' => [EpcTagUri::class, 'urn:epc:tag:giai-202:3.9521141.' . \str_repeat('1', 1069)];
        yield 'HIBC UDI' => [HibcUdi::class, '+' . \str_repeat('A', 120)];
        yield 'ISBT 128 UDI' => [IsbtUdi::class, '=/' . \str_repeat('A', 3123)];
        yield 'NDC' => [Ndc::class, '12345-1234-1234567'];
        yield 'Russian marking code' => [MarkingCode::class, \str_repeat('1', 263)];
        yield 'Russian marking identifier' => [MarkingIdentifier::class, \str_repeat('1', 263)];
        yield 'FMD pack code' => [FmdPackCode::class, "[)>\x1E06\x1D9N111234567842\x1D1T1A234B5\x1DD151231\x1DS1234567890123456\x1DZ" . \str_repeat('x', 470)];
        yield 'PPN' => [Ppn::class, '11' . \str_repeat('1', 37)];
        yield 'tobacco UI' => [TobaccoUnitIdentifier::class, \str_repeat('A', 59)];
        yield 'tobacco UI without time stamp' => [TobaccoUnitIdentifierWithoutTimeStamp::class, \str_repeat('A', 51)];
    }

    /**
     * @param class-string<StringConvertible> $class
     */
    #[DataProvider('overLongInputs')]
    public function testAnOverLongInputIsRefusedWithoutItsLength(string $class, string $input): void
    {
        $message = $this->messageOf(static fn (): StringConvertible => $class::from($input));

        self::assertStringNotContainsString((string) \strlen($input), $message);
    }

    /**
     * @return iterable<string, array{Closure(): mixed, non-empty-string}>
     */
    public static function inputFragments(): iterable
    {
        yield 'AI of the element string' => [static fn (): ElementString => ElementString::from('(422)99999'), '422'];
        yield 'excluded AI pair' => [static fn (): ElementString => ElementString::from('(01)09506000134352(02)04006381333931'), '02'];
        yield 'query AI of a Digital Link' => [static fn (): DigitalLink => DigitalLink::from('https://example.com/00/195201234567891232?21=1'), '21'];
        yield 'EPC encoding name' => [static fn (): EpcTagUri => EpcTagUri::from('urn:epc:tag:sgtin-96:3.9521141.012345.01'), 'sgtin'];
        yield 'HIBC supplemental DI' => [static fn (): HibcUdi => HibcUdi::from('+A123BJC5D6E71/$$7A/16D20111212/16D20111213Y'), '16D'];
        yield 'NDC segment lengths' => [static fn (): Ndc => Ndc::from('1234-123-12'), '4-3-2'];
    }

    /**
     * @param Closure(): mixed $build
     * @param non-empty-string $fragment
     */
    #[DataProvider('inputFragments')]
    public function testAMessageEchoesNoFragmentOfTheInput(Closure $build, string $fragment): void
    {
        self::assertStringNotContainsString($fragment, $this->messageOf($build));
    }

    /**
     * @param Closure(): mixed $build
     */
    private function messageOf(Closure $build): string
    {
        try {
            $build();
        } catch (InvalidValueObjectException $exception) {
            return $exception->getMessage();
        }

        self::fail('The input must be refused.');
    }
}
