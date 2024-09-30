<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsPartialCommand;
use AssetGrabber\Commands\ThemesPartialCommand;
use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\ThemeListService;
use Laminas\ServiceManager\ServiceManager;

class ThemesPartialCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemesPartialCommand
    {
        $listService = $serviceManager->get(ThemeListService::class);
        return new ThemesPartialCommand($listService);
    }
}
