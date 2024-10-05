<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Services\Plugins\PluginMetadataService;
use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

class PluginMetadataServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginMetadataService
    {
        $pdo = $serviceManager->get(ExtendedPdoInterface::class);
        return new PluginMetadataService($pdo);
    }
}
