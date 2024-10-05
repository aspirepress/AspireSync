<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\UtilUploadCommand;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class UtilUploadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): UtilUploadCommand
    {
        $metadata  = $serviceManager->get(PluginMetadataService::class);
        $themeMetadata  = $serviceManager->get(ThemesMetadataService::class);
        $flysystem = $serviceManager->get('util:upload');
        return new UtilUploadCommand($metadata, $themeMetadata, $flysystem);
    }
}
