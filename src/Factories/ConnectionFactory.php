<?php

declare(strict_types=1);

namespace App\Factories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Tools\DsnParser;
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
        $connection->executeStatement('PRAGMA foreign_keys = ON');
        try {
            $connection->executeQuery("select 1 from sync limit 1");
        } catch (TableNotFoundException) {
            $init_script = file_get_contents($db_init_file);
            // $connection->executeQuery($init_script); // silently fails!
            $connection->getNativeConnection()->exec($init_script); // @phpstan-ignore method.nonObject
            $connection->executeQuery("select 666 from sync limit 1");
        }
        return $connection;
    }
}
