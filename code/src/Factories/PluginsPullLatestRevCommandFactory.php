<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsPullLatestRevCommand;
use AssetGrabber\Services\PluginListService;
use Laminas\ServiceManager\ServiceManager;

class PluginsPullLatestRevCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsPullLatestRevCommand
    {
        $pluginListService = $serviceManager->get(PluginListService::class);
        return new PluginsPullLatestRevCommand($pluginListService);
    }
}