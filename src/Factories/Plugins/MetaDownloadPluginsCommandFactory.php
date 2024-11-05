<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Commands\Plugins\MetaDownloadPluginsCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\WPEndpointClient;
use Laminas\ServiceManager\ServiceManager;

class MetaDownloadPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaDownloadPluginsCommand
    {
        $pluginList = $serviceManager->get(PluginListService::class);
        $pluginMeta = $serviceManager->get(PluginMetadataService::class);
        $statsMeta  = $serviceManager->get(StatsMetadataService::class);
        $wpClient   = $serviceManager->get(WPEndpointClient::class);

        return new MetaDownloadPluginsCommand($pluginList, $pluginMeta, $statsMeta, $wpClient);
    }
}
