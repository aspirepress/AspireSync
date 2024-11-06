<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Services\Plugins\PluginDownloadFromWpService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use GuzzleHttp\Client as GuzzleClient;
use Laminas\ServiceManager\ServiceManager;

class PluginDownloadFromWpServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginDownloadFromWpService
    {
        return new PluginDownloadFromWpService(
            pluginMetadataService: $serviceManager->get(PluginMetadataService::class),
            guzzle: $serviceManager->get(GuzzleClient::class)
        );
    }
}
