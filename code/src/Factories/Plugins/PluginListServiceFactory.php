<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\PluginMetadataService;
use AssetGrabber\Services\RevisionMetadataService;
use Laminas\ServiceManager\ServiceManager;

class PluginListServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginListService
    {
        $pluginService   = $serviceManager->get(PluginMetadataService::class);
        $revisionService = $serviceManager->get(RevisionMetadataService::class);
        return new PluginListService($pluginService, $revisionService);
    }
}
