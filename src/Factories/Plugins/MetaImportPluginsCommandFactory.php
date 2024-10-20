<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Commands\Plugins\MetaImportPluginsCommand;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
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
