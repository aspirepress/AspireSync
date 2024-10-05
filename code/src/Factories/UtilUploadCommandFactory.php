<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\UtilUploadCommand;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class UtilUploadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): UtilUploadCommand
    {
        $metadata  = $serviceManager->get(PluginMetadataService::class);
        $flysystem = $serviceManager->get('util:upload:plugins');
        return new UtilUploadCommand($metadata, $flysystem);
    }
}
