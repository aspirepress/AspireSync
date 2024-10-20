<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use Aura\Sql\ExtendedPdo;
use Laminas\ServiceManager\ServiceManager;

class ExtendedPdoFactory
{
    public function __invoke(ServiceManager $serviceManager): ExtendedPdo
    {
        $config   = $serviceManager->get('config');
        $dbConfig = $config['database'];

        $dsn = 'pgsql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['name'];

        $pdo = new ExtendedPdo($dsn, $dbConfig['user'], $dbConfig['pass']);
        $pdo->exec('SET search_path TO ' . $dbConfig['schema']);
        return $pdo;
    }
}
