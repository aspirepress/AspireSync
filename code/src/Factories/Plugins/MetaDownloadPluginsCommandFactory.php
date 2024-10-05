<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Plugins;

use AssetGrabber\Commands\Plugins\MetaDownloadPluginsCommand;
use AssetGrabber\Services\Plugins\PluginListService;
use Laminas\ServiceManager\ServiceManager;

class MetaDownloadPluginsCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaDownloadPluginsCommand
    {
        $pluginListService = $serviceManager->get(PluginListService::class);

        return new MetaDownloadPluginsCommand($pluginListService);
    }
}
