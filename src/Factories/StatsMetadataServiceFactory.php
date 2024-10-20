<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use AspirePress\AspireSync\Services\StatsMetadataService;
use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

class StatsMetadataServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): StatsMetadataService
    {
        $pdo = $serviceManager->get(ExtendedPdoInterface::class);
        return new StatsMetadataService($pdo);
    }
}
