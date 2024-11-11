<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use SQLite3Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ConnectionFactory
{
    public function __invoke(
        #[Autowire(param: 'db_url')] string $db_url,
        #[Autowire(param: 'db_init_file')] string $db_init_file,
    ): Connection {
        $params     = (new DsnParser())->parse($db_url);
        $connection = DriverManager::getConnection($params);
        return $this->initialize($connection, $db_init_file);
    }

    private function initialize(Connection $connection, string $db_init_file): Connection
    {
        $pdo = $connection->getNativeConnection();
        $pdo->exec('PRAGMA foreign_keys = ON');
        try {
            $pdo->query("select 1 from sync_plugins limit 1");
        } catch (SQLite3Exception) {
            $pdo->exec(file_get_contents($db_init_file));
            $pdo->query("select 1 from sync_plugins limit 1");
        }
        return $connection;
    }
}
