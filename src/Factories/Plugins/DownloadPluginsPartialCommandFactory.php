<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Commands\Plugins\DownloadPluginsPartialCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadPluginsPartialCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadPluginsPartialCommand
    {
        return new DownloadPluginsPartialCommand(
            pluginListService: $serviceManager->get(PluginListService::class),
            pluginMetadataService: $serviceManager->get(PluginMetadataService::class),
            statsMetadataService: $serviceManager->get(StatsMetadataService::class)
        );
    }
}
