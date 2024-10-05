<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Commands\Plugins\DownloadPluginsCommand;
use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadPluginsCommand
    {
        $metadata      = $serviceManager->get(PluginMetadataService::class);
        $pluginService = $serviceManager->get(PluginListService::class);
        return new DownloadPluginsCommand($pluginService, $metadata);
    }
}
