<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsPartialCommand;
use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\PluginMetadataService;
use Laminas\ServiceManager\ServiceManager;

class PluginsPartialCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsPartialCommand
    {
        $listService = $serviceManager->get(PluginListService::class);
        $metadata = $serviceManager->get(PluginMetadataService::class);
        return new PluginsPartialCommand($listService, $metadata);
    }
}
