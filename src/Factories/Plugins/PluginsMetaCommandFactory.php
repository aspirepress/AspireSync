<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Commands\Plugins\PluginsMetaCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\WPEndpointClient;
use Laminas\ServiceManager\ServiceManager;

class PluginsMetaCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsMetaCommand
    {
        return new PluginsMetaCommand(
            pluginListService: $serviceManager->get(PluginListService::class),
            pluginMetadataService: $serviceManager->get(PluginMetadataService::class),
            statsMetadataService: $serviceManager->get(StatsMetadataService::class),
            wpClient: $serviceManager->get(WPEndpointClient::class)
        );
    }
}
