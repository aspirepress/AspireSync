<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

class PluginMetadataServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginMetadataService
    {
        return new PluginMetadataService($serviceManager->get(ExtendedPdoInterface::class));
    }
}
