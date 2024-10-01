<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\InternalPluginDownloadCommand;
use AssetGrabber\Services\PluginDownloadService;
use Laminas\ServiceManager\ServiceManager;

class InternalPluginDownloadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): InternalPluginDownloadCommand
    {
        $downloadService = $serviceManager->get(PluginDownloadService::class);
        return new InternalPluginDownloadCommand($downloadService);
    }
}
