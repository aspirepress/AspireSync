<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Services\Plugins\PluginDownloadFromWpService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
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
