<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsImportMetaCommand;
use AssetGrabber\Services\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class PluginsImportMetaCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsImportMetaCommand
    {
        $metadataService = $serviceManager->get(PluginMetadataService::class);
        return new PluginsImportMetaCommand($metadataService);
    }
}
