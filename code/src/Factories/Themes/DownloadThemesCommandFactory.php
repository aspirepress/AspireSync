<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Commands\Plugins\DownloadPluginsCommand;
use AssetGrabber\Commands\Themes\DownloadThemesCommand;
use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\Themes\ThemeListService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadThemesCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadThemesCommand
    {
        $metadata      = $serviceManager->get(ThemesMetadataService::class);
        $listService = $serviceManager->get(ThemeListService::class);
        return new DownloadThemesCommand($listService, $metadata);
    }
}
