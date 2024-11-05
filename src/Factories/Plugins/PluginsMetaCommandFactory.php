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
        $pluginList = $serviceManager->get(PluginListService::class);
        $pluginMeta = $serviceManager->get(PluginMetadataService::class);
        $statsMeta  = $serviceManager->get(StatsMetadataService::class);
        $wpClient   = $serviceManager->get(WPEndpointClient::class);

        return new PluginsMetaCommand($pluginList, $pluginMeta, $statsMeta, $wpClient);
    }
}
