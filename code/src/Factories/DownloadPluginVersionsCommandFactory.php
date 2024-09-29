<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\DownloadPluginVersionsCommand;
use AssetGrabber\Commands\GrabPluginsCommand;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use Laminas\ServiceManager\ServiceManager;

ini_set('memory_limit', '2G');

class DownloadPluginVersionsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadPluginVersionsCommand
    {
        $pluginService = $serviceManager->get(PluginListService::class);
        $downloadService = $serviceManager->get(PluginDownloadService::class);
        return new DownloadPluginVersionsCommand($pluginService, $downloadService);
    }
}