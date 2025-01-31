<?php
declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude([
        'svn',
        'tmp',
        'var',
        'vendor',
    ])
    ->notPath([
        '_ide_helper.php',
        '.phpstorm.meta.php',
    ]);

return (new Config())
    ->setRules([
        '@PER-CS2.0' => true,
        '@PHP84Migration' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.cache/.php_cs.cache')
    ->setParallelConfig(ParallelConfigFactory::detect());
