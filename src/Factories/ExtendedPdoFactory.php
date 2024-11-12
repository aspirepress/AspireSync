<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use Aura\Sql\ExtendedPdo;
use PDOException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ExtendedPdoFactory
{
    public function __invoke(
        #[Autowire(param: 'db_file')] string $db_file,
        #[Autowire(param: 'db_init_file')] string $db_init_file,
    ): ExtendedPdo {
        $dsn = "sqlite:$db_file";
        $pdo = new ExtendedPdo($dsn);
        return $this->initialize($pdo, $db_init_file);
    }

    private function initialize(ExtendedPdo $pdo, string $db_init_file): ExtendedPdo
    {
        $pdo->connect();
        $pdo->exec('PRAGMA foreign_keys = ON');
        try {
            $pdo->query("select 1 from sync limit 1");
        } catch (PDOException $e) {
            $pdo->exec(file_get_contents($db_init_file));
        }
        return $pdo;
    }
}
