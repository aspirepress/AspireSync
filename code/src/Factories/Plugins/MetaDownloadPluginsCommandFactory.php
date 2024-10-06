<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Commands\Plugins\MetaDownloadPluginsCommand;
use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\StatsMetadataService;
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
