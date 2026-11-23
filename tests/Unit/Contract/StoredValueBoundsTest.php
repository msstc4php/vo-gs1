<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Contract;

use MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcPureIdentityUri;
use MSSTC4PHP\ValueObject\Gs1\Epc\EpcTagUri;
use MSSTC4PHP\ValueObject\Gs1\Key\Cpid;
use MSSTC4PHP\ValueObject\Gs1\Key\Gcn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gdti;
use MSSTC4PHP\ValueObject\Gs1\Key\Giai;
use MSSTC4PHP\ValueObject\Gs1\Key\Ginc;
use MSSTC4PHP\ValueObject\Gs1\Key\Gln;
use MSSTC4PHP\ValueObject\Gs1\Key\Gmn;
use MSSTC4PHP\ValueObject\Gs1\Key\Grai;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsin;
use MSSTC4PHP\ValueObject\Gs1\Key\Gsrn;
use MSSTC4PHP\ValueObject\Gs1\Key\Gtin;
use MSSTC4PHP\ValueObject\Gs1\Key\Sscc;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\FmdPackCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\Ppn;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifier;
use MSSTC4PHP\ValueObject\Gs1\Marking\EU\TobaccoUnitIdentifierWithoutTimeStamp;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingCode;
use MSSTC4PHP\ValueObject\Gs1\Marking\RU\MarkingIdentifier;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\Gs1BasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\HibccBasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\IccbbaBasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\BasicUdiDi\IfaBasicUdiDi;
use MSSTC4PHP\ValueObject\Gs1\Udi\Gs1Udi;
use MSSTC4PHP\ValueObject\Gs1\Udi\HibcUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\IsbtUdi;
use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc;
use MSSTC4PHP\ValueObject\Gs1\Udi\Ndc12;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;

/**
 * The public `MAX_LENGTH` of every value object: the longest stored value, frozen for 1.x (bridges size columns by it).
 */
#[CoversNothing]
final class StoredValueBoundsTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string, positive-int}>
     */
    public static function bounds(): iterable
    {
        yield 'Cpid' => [Cpid::class, 30];
        yield 'Gcn' => [Gcn::class, 25];
        yield 'Gdti' => [Gdti::class, 30];
        yield 'Giai' => [Giai::class, 30];
        yield 'Ginc' => [Ginc::class, 30];
        yield 'Gln' => [Gln::class, 13];
        yield 'Gmn' => [Gmn::class, 25];
        yield 'Grai' => [Grai::class, 30];
        yield 'Gsin' => [Gsin::class, 17];
        yield 'Gsrn' => [Gsrn::class, 18];
        yield 'Gtin' => [Gtin::class, 14];
        yield 'Sscc' => [Sscc::class, 18];
        yield 'ElementString' => [ElementString::class, 14178];
        yield 'DigitalLink' => [DigitalLink::class, 14178];
        yield 'EpcPureIdentityUri' => [EpcPureIdentityUri::class, 1024];
        yield 'EpcTagUri' => [EpcTagUri::class, 1024];
        yield 'Gs1Udi' => [Gs1Udi::class, 14178];
        yield 'HibcUdi' => [HibcUdi::class, 113];
        yield 'IsbtUdi' => [IsbtUdi::class, 3123];
        yield 'Ndc' => [Ndc::class, 13];
        yield 'Ndc12' => [Ndc12::class, 14];
        yield 'Gs1BasicUdiDi' => [Gs1BasicUdiDi::class, 25];
        yield 'HibccBasicUdiDi' => [HibccBasicUdiDi::class, 25];
        yield 'IccbbaBasicUdiDi' => [IccbbaBasicUdiDi::class, 18];
        yield 'IfaBasicUdiDi' => [IfaBasicUdiDi::class, 25];
        yield 'MarkingCode' => [MarkingCode::class, 255];
        yield 'MarkingIdentifier' => [MarkingIdentifier::class, 74];
        yield 'FmdPackCode' => [FmdPackCode::class, 514];
        yield 'Ppn' => [Ppn::class, 28];
        yield 'TobaccoUnitIdentifier' => [TobaccoUnitIdentifier::class, 50];
        yield 'TobaccoUnitIdentifierWithoutTimeStamp' => [TobaccoUnitIdentifierWithoutTimeStamp::class, 42];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('bounds')]
    public function testEveryValueObjectPublishesItsStoredValueBound(string $class, int $bound): void
    {
        $constant = new ReflectionClassConstant($class, 'MAX_LENGTH');

        self::assertTrue($constant->isPublic());
        self::assertSame($bound, $constant->getValue());
    }

    public function testTheLongestMarkingIdentifierIsTheTobaccoGroupPackWithAnEscapedSerial(): void
    {
        // (01) + 14, (21) + 20 characters each escaped as "\(", (8005) + 6.
        $longest = '(01)04600439931256(21)' . \str_repeat('\(', 20) . '(8005)012500';

        self::assertSame(MarkingIdentifier::MAX_LENGTH, \strlen((string) MarkingIdentifier::from($longest)));
    }
}
