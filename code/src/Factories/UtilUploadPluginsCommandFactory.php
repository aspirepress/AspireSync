<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\UtilUploadPluginsCommand;
use AssetGrabber\Services\PluginMetadataService;
use Aws\S3\S3Client;
use Laminas\ServiceManager\ServiceManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class UtilUploadPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): UtilUploadPluginsCommand
    {
        $metadata = $serviceManager->get(PluginMetadataService::class);
        $flysystem = $serviceManager->get('util:upload:plugins');
        return new UtilUploadPluginsCommand($metadata, $flysystem);
    }
}