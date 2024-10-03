<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsGrabCommand;
use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class PluginsGrabCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsGrabCommand
    {
        $pluginService = $serviceManager->get(PluginListService::class);
        $pluginMetadata = $serviceManager->get(PluginMetadataService::class);
        return new PluginsGrabCommand($pluginService, $pluginMetadata);
    }
}
