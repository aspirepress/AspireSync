<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use Aura\Sql\ExtendedPdo;
use Laminas\ServiceManager\ServiceManager;
use PDOException;

class ExtendedPdoFactory
{
    public function __invoke(ServiceManager $serviceManager): ExtendedPdo
    {
        $config = $serviceManager->get('config');
        $pdo    = new ExtendedPdo($config['database']['dsn']);
        return $this->initialize($pdo, $serviceManager);
    }

    private function initialize(ExtendedPdo $pdo, ServiceManager $serviceManager): ExtendedPdo
    {
        $pdo->connect();
        $pdo->exec('PRAGMA foreign_keys = ON');
        try {
            $pdo->query("select 1 from sync_plugins limit 1");
        } catch (PDOException $e) {
            $init_script = $serviceManager->get('config')['database']['init_script'];
            $pdo->exec(file_get_contents($init_script));
        }
        return $pdo;
    }
}
