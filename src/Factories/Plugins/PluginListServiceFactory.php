<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\SvnService;
use Laminas\ServiceManager\ServiceManager;

class PluginListServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginListService
    {
        return new PluginListService(
            svnService: $serviceManager->get(SvnService::class),
            pluginService: $serviceManager->get(PluginMetadataService::class),
            revisionService: $serviceManager->get(RevisionMetadataService::class)
        );
    }
}
