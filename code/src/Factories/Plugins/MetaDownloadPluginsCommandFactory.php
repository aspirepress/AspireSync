<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Commands\Plugins\MetaDownloadPluginsCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use Laminas\ServiceManager\ServiceManager;

class MetaDownloadPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaDownloadPluginsCommand
    {
        $pluginListService = $serviceManager->get(PluginListService::class);
        $statsMeta         = $serviceManager->get(StatsMetadataService::class);

        return new MetaDownloadPluginsCommand($pluginListService, $statsMeta);
    }
}
