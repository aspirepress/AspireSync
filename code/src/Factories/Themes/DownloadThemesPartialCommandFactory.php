<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Commands\Themes\DownloadThemesPartialCommand;
use AssetGrabber\Services\StatsMetadataService;
use AssetGrabber\Services\Themes\ThemeListService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadThemesPartialCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadThemesPartialCommand
    {
        $listService = $serviceManager->get(ThemeListService::class);
        $metadata    = $serviceManager->get(ThemesMetadataService::class);
        $statsMeta   = $serviceManager->get(StatsMetadataService::class);

        return new DownloadThemesPartialCommand($listService, $metadata, $statsMeta);
    }
}
