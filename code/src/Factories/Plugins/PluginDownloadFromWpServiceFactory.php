<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Services\PluginDownloadFromWpService;
use AssetGrabber\Services\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class PluginDownloadFromWpServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginDownloadFromWpService
    {
        $ua                = $serviceManager->get('config')['user-agents'];
        $pluginMetaService = $serviceManager->get(PluginMetadataService::class);
        return new PluginDownloadFromWpService($ua, $pluginMetaService);
    }
}
