<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Commands\Plugins\DownloadPluginsPartialCommand;
use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadPluginsPartialCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadPluginsPartialCommand
    {
        $listService = $serviceManager->get(PluginListService::class);
        $metadata    = $serviceManager->get(PluginMetadataService::class);
        return new DownloadPluginsPartialCommand($listService, $metadata);
    }
}
