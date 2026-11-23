<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Gs1\Tests\Unit\Internal;

use MSSTC4PHP\ValueObject\Gs1\Tests\Support\AiTableGenerator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * BC promise (README "Backward compatibility"): a dictionary edition that removes or tightens an AI must keep the
 * 1.0 spec as legacy. Legacy specs are not implemented yet, so until then every 1.0 data line must stay as it is.
 */
#[CoversNothing]
final class AiTableKeepsThe1Dot0AisTest extends TestCase
{
    private const string BASELINE = 'tests/Fixtures/ai-dictionary-1.0.txt';

    public function testEveryDataLineOfThe1Dot0EditionIsUnchanged(): void
    {
        $root = \dirname(__DIR__, 3);
        $current = $this->dataLines((string) \file_get_contents($root . '/' . AiTableGenerator::DICTIONARY));
        $baseline = $this->dataLines((string) \file_get_contents($root . '/' . self::BASELINE));

        self::assertCount(224, $baseline);
        self::assertSame(
            [],
            \array_values(\array_diff($baseline, $current)),
            'The dictionary removed or changed these 1.0 AI lines: keep their 1.0 spec accepted as legacy before upgrading.',
        );
    }

    /**
     * @return list<string> the data lines without title comments, whitespace collapsed
     */
    private function dataLines(string $dictionary): array
    {
        $lines = [];
        foreach (\explode("\n", $dictionary) as $line) {
            $data = \trim((string) \preg_replace('/\s+/', ' ', \explode('#', $line, 2)[0]));
            if ($data !== '') {
                $lines[] = $data;
            }
        }

        return $lines;
    }
}
