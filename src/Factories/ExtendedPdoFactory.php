<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use Aura\Sql\ExtendedPdo;
use PDOException;

class ExtendedPdoFactory
{
    public const string DEFAULT_INIT_SCRIPT = __DIR__ . '/../../config/schema.sql';

    public function __invoke(string $db_file): ExtendedPdo
    {
        $dsn = "sqlite:$db_file";
        $pdo = new ExtendedPdo($dsn);
        return $this->initialize($pdo);
    }

    private function initialize(ExtendedPdo $pdo): ExtendedPdo
    {
        $pdo->connect();
        $pdo->exec('PRAGMA foreign_keys = ON');
        try {
            $pdo->query("select 1 from sync_plugins limit 1");
        } catch (PDOException $e) {
            $init_script = getenv('DB_INIT_SCRIPT') ?: self::DEFAULT_INIT_SCRIPT;
            $pdo->exec(file_get_contents($init_script));
        }
        return $pdo;
    }
}
