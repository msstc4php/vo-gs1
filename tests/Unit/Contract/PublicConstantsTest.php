<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Contract;

use MSSTC4PHP\ValueObject\Gs1\DigitalLink\DigitalLink;
use MSSTC4PHP\ValueObject\Gs1\ElementString\ElementString;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionClassConstant;

/**
 * Public constants that alias `Internal\` ones: a change there must not silently change a public value.
 */
#[CoversNothing]
final class PublicConstantsTest extends TestCase
{
    public function testTheCanonicalDigitalLinkDomainIsFrozen(): void
    {
        self::assertSame('https://id.gs1.org', new ReflectionClassConstant(DigitalLink::class, 'CANONICAL_DOMAIN')->getValue());
    }

    /**
     * Moves with each dictionary edition (a minor), always together with the vendored dictionary and the README.
     */
    public function testTheAiTableReleaseNamesTheVendoredDictionary(): void
    {
        $dictionary = (string) \file_get_contents(__DIR__ . '/../../../resources/gs1-syntax-dictionary.txt');
        $readme = (string) \file_get_contents(__DIR__ . '/../../../README.md');

        self::assertSame('2026-01-27', new ReflectionClassConstant(ElementString::class, 'AI_TABLE_RELEASE')->getValue());
        self::assertStringContainsString('# Release: ' . ElementString::AI_TABLE_RELEASE . "\n", $dictionary);
        self::assertStringContainsString('release ' . ElementString::AI_TABLE_RELEASE . ',', $readme);
    }
}
