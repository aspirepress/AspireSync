<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Commands\Themes\DownloadThemesPartialCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadThemesPartialCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadThemesPartialCommand
    {
        return new DownloadThemesPartialCommand(
            themeListService: $serviceManager->get(ThemeListService::class),
            themesMetadataService: $serviceManager->get(ThemesMetadataService::class),
            statsMetadataService: $serviceManager->get(StatsMetadataService::class)
        );
    }
}
