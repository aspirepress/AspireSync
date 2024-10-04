<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Commands\Plugins\PluginsGrabCommand;
use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class PluginsGrabCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsGrabCommand
    {
        $metadata      = $serviceManager->get(PluginMetadataService::class);
        $pluginService = $serviceManager->get(PluginListService::class);
        return new PluginsGrabCommand($pluginService, $metadata);
    }
}
