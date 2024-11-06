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
        return new StatsMetadataService($serviceManager->get(ExtendedPdoInterface::class));
    }
}
