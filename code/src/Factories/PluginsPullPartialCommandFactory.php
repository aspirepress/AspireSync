<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsPullPartialCommand;
use AssetGrabber\Services\PluginListService;
use Laminas\ServiceManager\ServiceManager;

class PluginsPullPartialCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsPullPartialCommand
    {
        $listService = $serviceManager->get(PluginListService::class);
        return new PluginsPullPartialCommand($listService);
    }
}
