<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use LogicException;
use MSSTC4PHP\ValueObject\Gs1\Internal\GmnCheckCharacters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(GmnCheckCharacters::class)]
final class GmnCheckCharactersTest extends TestCase
{
    public function testComputesTheGeneralSpecificationsExample(): void
    {
        // GS1 General Specifications 26.0, Figure 7-15.
        self::assertSame('2K', GmnCheckCharacters::compute('1987654Ad4X4bL5ttr2310c'));
    }

    /**
     * Test vectors of the GS1 Barcode Syntax Tests (lint_csumalpha.c, release 2026-01-27) within 23 characters.
     *
     * @return iterable<string, array{string, non-empty-string}>
     */
    public static function syntaxTestVectors(): iterable
    {
        yield 'all digits' => ['12345678901234567890123', 'NT'];
        yield 'upper case A-M' => ['12345_ABCDEFGHIJKLM', 'CP'];
        yield 'upper case N-Z' => ['12345_NOPQRSTUVWXYZ', 'DN'];
        yield 'lower case a-m' => ['12345_abcdefghijklm', 'N3'];
        yield 'lower case n-z' => ['12345_nopqrstuvwxyz', 'P2'];
        yield 'punctuation' => ['12345_!"%&\'()*+,-./', 'LC'];
        yield 'digits and symbols' => ['12345_0123456789:;<=>?', '62'];
        yield 'mixed' => ['57453Uq3qA<H', '67'];
        yield 'first character' => ['00000!', 'HV'];
        yield 'highest values' => ['99999zzzzzzzzzzzzzzzzzz', 'T2'];
        yield 'one character' => ['!', '22'];
    }

    #[DataProvider('syntaxTestVectors')]
    public function testComputesTheSyntaxTestVectors(string $body, string $expected): void
    {
        self::assertSame($expected, GmnCheckCharacters::compute($body));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidBodies(): iterable
    {
        yield 'empty' => [''];
        yield 'space' => ['1987654 d4'];
        yield 'hash' => ['1987654#d4'];
        yield 'non-ASCII' => ['1987654Äd4'];
        yield '24 characters' => [\str_repeat('1', 24)];
    }

    #[DataProvider('invalidBodies')]
    public function testRefusesABodyOutsideCset82OrTooLong(string $body): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('GMN check character input must be 1 to 23 characters of GS1 CSET 82.');

        GmnCheckCharacters::compute($body);
    }

    public function testAcceptsTheLongestBody(): void
    {
        self::assertSame(2, \strlen(GmnCheckCharacters::compute(\str_repeat('z', 23))));
    }
}
