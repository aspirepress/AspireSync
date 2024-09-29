<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\GrabPluginsCommand;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use Laminas\ServiceManager\ServiceManager;

class GrabPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): GrabPluginsCommand
    {
        $pluginService = $serviceManager->get(PluginListService::class);
        $downloadService = $serviceManager->get(PluginDownloadService::class);
        return new GrabPluginsCommand($pluginService, $downloadService);
    }
}