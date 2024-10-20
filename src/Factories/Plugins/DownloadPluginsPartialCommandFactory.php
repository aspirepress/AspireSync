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
        $listService = $serviceManager->get(PluginListService::class);
        $metadata    = $serviceManager->get(PluginMetadataService::class);
        $statsMeta   = $serviceManager->get(StatsMetadataService::class);

        return new DownloadPluginsPartialCommand($listService, $metadata, $statsMeta);
    }
}
