<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Commands\Plugins\MetaImportPluginsCommand;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\StatsMetadataService;
use Laminas\ServiceManager\ServiceManager;

class MetaImportPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaImportPluginsCommand
    {
        $metadataService = $serviceManager->get(PluginMetadataService::class);
        $statsMeta       = $serviceManager->get(StatsMetadataService::class);

        return new MetaImportPluginsCommand($metadataService, $statsMeta);
    }
}
