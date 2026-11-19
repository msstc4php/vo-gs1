<?php

declare(strict_types=1);

use MSSTC4PHP\ValueObject\Gs1\Tests\Support\AiTableGenerator;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$dictionary = file_get_contents($root . '/' . AiTableGenerator::DICTIONARY);
if ($dictionary === false) {
    fwrite(STDERR, 'Cannot read ' . AiTableGenerator::DICTIONARY . "\n");

    exit(1);
}

file_put_contents($root . '/' . AiTableGenerator::TARGET, new AiTableGenerator()->generate($dictionary));
echo 'wrote ' . AiTableGenerator::TARGET . "\n";
