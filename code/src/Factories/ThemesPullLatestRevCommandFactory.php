<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\ThemesPullLatestRevCommand;
use AssetGrabber\Services\ThemeListService;
use Laminas\ServiceManager\ServiceManager;

class ThemesPullLatestRevCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemesPullLatestRevCommand
    {
        $pluginListService = $serviceManager->get(ThemeListService::class);
        return new ThemesPullLatestRevCommand($pluginListService);
    }
}
