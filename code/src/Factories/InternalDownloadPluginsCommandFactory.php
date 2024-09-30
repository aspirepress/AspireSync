<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\InternalDownloadPluginsCommand;
use AssetGrabber\Commands\GrabPluginsCommand;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use Laminas\ServiceManager\ServiceManager;

ini_set('memory_limit', '2G');

class InternalDownloadPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): InternalDownloadPluginsCommand
    {
        $pluginService = $serviceManager->get(PluginListService::class);
        $downloadService = $serviceManager->get(PluginDownloadService::class);
        return new InternalDownloadPluginsCommand($pluginService, $downloadService);
    }
}
