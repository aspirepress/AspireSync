<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsMetaCommand;
use AssetGrabber\Services\PluginListService;
use Laminas\ServiceManager\ServiceManager;

class PluginsMetaCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsMetaCommand
    {
        $pluginListService = $serviceManager->get(PluginListService::class);

        return new PluginsMetaCommand($pluginListService);
    }
}
