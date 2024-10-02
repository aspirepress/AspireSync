<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Services\PluginMetadataService;
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
