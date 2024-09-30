<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\InternalPluginDownloadCommand;
use AssetGrabber\Commands\PluginsGrabCommand;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use Laminas\ServiceManager\ServiceManager;

ini_set('memory_limit', '2G');

class InternalPluginDownloadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): InternalPluginDownloadCommand
    {
        $downloadService = $serviceManager->get(PluginDownloadService::class);
        return new InternalPluginDownloadCommand($downloadService);
    }
}
