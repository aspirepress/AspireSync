<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Helpers;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;

abstract class FunctionalTestHelper
{
    public static function getDb(): ExtendedPdoInterface
    {
        $pdo = new ExtendedPdo("sqlite::memory:");
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(file_get_contents(__DIR__ . '/../../config/schema.sql'));
        return $pdo;
    }
}
