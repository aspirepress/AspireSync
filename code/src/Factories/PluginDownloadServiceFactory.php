<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class PluginDownloadServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginDownloadService
    {
        $ua                = $serviceManager->get('config')['user-agents'];
        $pluginMetaService = $serviceManager->get(PluginMetadataService::class);
        return new PluginDownloadService($ua, $pluginMetaService);
    }
}
