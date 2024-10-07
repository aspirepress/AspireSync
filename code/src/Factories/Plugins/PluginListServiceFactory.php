<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\RevisionMetadataService;
use AssetGrabber\Services\SvnService;
use Laminas\ServiceManager\ServiceManager;

class PluginListServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginListService
    {
        $pluginService   = $serviceManager->get(PluginMetadataService::class);
        $revisionService = $serviceManager->get(RevisionMetadataService::class);
        $svnService      = $serviceManager->get(SvnService::class);
        return new PluginListService($svnService, $pluginService, $revisionService);
    }
}
