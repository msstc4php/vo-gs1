<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Gs1\Tests\Support\AiTableGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * The committed table must equal fresh generator output. Regenerate with `php tools/generate-ai-table.php` after
 * replacing resources/gs1-syntax-dictionary.txt with a new release.
 */
#[CoversNothing]
final class AiTableUpToDateTest extends TestCase
{
    public function testCommittedTableMatchesTheGenerator(): void
    {
        $root = \dirname(__DIR__, 3);
        $dictionary = (string) \file_get_contents($root . '/' . AiTableGenerator::DICTIONARY);

        $first = new AiTableGenerator()->generate($dictionary);

        self::assertSame($first, new AiTableGenerator()->generate($dictionary), 'The generator is not deterministic.');
        self::assertSame(
            $first,
            \file_get_contents($root . '/' . AiTableGenerator::TARGET),
            AiTableGenerator::TARGET . ' is stale: run php tools/generate-ai-table.php',
        );
    }
}
