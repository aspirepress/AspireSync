<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Commands\Plugins\DownloadPluginsCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadPluginsCommand
    {
        $metadata      = $serviceManager->get(PluginMetadataService::class);
        $pluginService = $serviceManager->get(PluginListService::class);
        $statsMeta     = $serviceManager->get(StatsMetadataService::class);
        return new DownloadPluginsCommand($pluginService, $metadata, $statsMeta);
    }
}
