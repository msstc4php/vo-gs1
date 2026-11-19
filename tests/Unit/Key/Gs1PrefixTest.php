<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Key;

use MSSTC4PHP\ValueObject\Gs1\Exception\InvalidIdentificationKeyException;
use MSSTC4PHP\ValueObject\Gs1\Internal\KeyRules;
use MSSTC4PHP\ValueObject\Gs1\Key\Gs1Prefix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Gs1Prefix::class)]
#[CoversClass(KeyRules::class)]
final class Gs1PrefixTest extends TestCase
{
    /**
     * GS1 General Specifications 26.0, Table 1-4.
     *
     * @return iterable<string, array{string, Gs1Prefix}>
     */
    public static function table14(): iterable
    {
        yield '978 ISBN' => ['9780306406157', Gs1Prefix::Isbn];
        yield '979 ISBN' => ['9791234567896', Gs1Prefix::Isbn];
        yield '977 ISSN' => ['9771234567003', Gs1Prefix::Issn];
        yield '20 restricted' => ['2012345678900', Gs1Prefix::RestrictedCirculation];
        yield '29 restricted' => ['2912345678900', Gs1Prefix::RestrictedCirculation];
        yield '02 restricted' => ['0212345678900', Gs1Prefix::RestrictedCirculation];
        yield '04 restricted' => ['0412345678900', Gs1Prefix::RestrictedCirculation];
        yield '19 member organisation' => ['1912345678900', Gs1Prefix::MemberOrganisation];
        yield '30 member organisation' => ['3012345678900', Gs1Prefix::MemberOrganisation];
        yield '03 member organisation' => ['0312345678900', Gs1Prefix::MemberOrganisation];
        yield '05 member organisation' => ['0512345678900', Gs1Prefix::MemberOrganisation];
        yield '952 demonstration' => ['9521234567890', Gs1Prefix::Demonstration];
        yield '980 refund receipts' => ['9801234567890', Gs1Prefix::RefundReceipt];
        yield '981 coupon' => ['9811234567890', Gs1Prefix::Coupon];
        yield '983 coupon' => ['9831234567890', Gs1Prefix::Coupon];
        yield '99 coupon' => ['9912345678900', Gs1Prefix::Coupon];
        yield '984 unknown' => ['9841234567890', Gs1Prefix::Unknown];
        yield '989 unknown' => ['9891234567890', Gs1Prefix::Unknown];
        yield '951 unknown' => ['9511234567890', Gs1Prefix::Unknown];
        yield '950 member organisation' => ['9501234567890', Gs1Prefix::MemberOrganisation];
        yield '0000000 restricted' => ['0000000123456', Gs1Prefix::RestrictedCirculation];
        yield '0000001 unused' => ['0000001123456', Gs1Prefix::Unused];
        yield '0000099 unused' => ['0000099123456', Gs1Prefix::Unused];
        yield '0000100 member organisation' => ['0000100123456', Gs1Prefix::MemberOrganisation];
        yield 'seven digits 0000001' => ['0000001', Gs1Prefix::Unused];
        yield 'seven digits 0000000' => ['0000000', Gs1Prefix::RestrictedCirculation];
        yield 'three digits only' => ['978', Gs1Prefix::Isbn];
        yield 'six digits cannot be unused' => ['000001', Gs1Prefix::MemberOrganisation];
    }

    #[DataProvider('table14')]
    public function testDecodesTable14(string $digits, Gs1Prefix $expected): void
    {
        self::assertSame($expected, Gs1Prefix::fromDigits($digits));
    }

    /**
     * Table 1-5.
     *
     * @return iterable<string, array{string, Gs1Prefix}>
     */
    public static function table15(): iterable
    {
        yield '000' => ['00012345', Gs1Prefix::RestrictedCirculation];
        yield '099' => ['09912345', Gs1Prefix::RestrictedCirculation];
        yield '100' => ['10012345', Gs1Prefix::MemberOrganisation];
        yield '199' => ['19912345', Gs1Prefix::MemberOrganisation];
        yield '200' => ['20012345', Gs1Prefix::RestrictedCirculation];
        yield '299' => ['29912345', Gs1Prefix::RestrictedCirculation];
        yield '300' => ['30012345', Gs1Prefix::MemberOrganisation];
        yield '952' => ['95212345', Gs1Prefix::Demonstration];
        yield 'three digits only' => ['952', Gs1Prefix::Demonstration];
    }

    #[DataProvider('table15')]
    public function testDecodesGtin8Prefixes(string $digits, Gs1Prefix $expected): void
    {
        self::assertSame($expected, Gs1Prefix::fromGtin8Digits($digits));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformed(): iterable
    {
        yield 'two digits' => ['97'];
        yield 'letters' => ['97A'];
        yield 'empty' => [''];
    }

    #[DataProvider('malformed')]
    public function testRefusesMalformedDigits(string $digits): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GS1 Prefix needs at least 3 leading digits.');

        Gs1Prefix::fromDigits($digits);
    }

    #[DataProvider('malformed')]
    public function testRefusesMalformedGtin8Digits(string $digits): void
    {
        $this->expectException(InvalidIdentificationKeyException::class);
        $this->expectExceptionMessage('GS1-8 Prefix needs at least 3 leading digits.');

        Gs1Prefix::fromGtin8Digits($digits);
    }
}
