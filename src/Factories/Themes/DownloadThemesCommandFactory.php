<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Commands\Themes\DownloadThemesCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class DownloadThemesCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): DownloadThemesCommand
    {
        return new DownloadThemesCommand(
            themeListService: $serviceManager->get(ThemeListService::class),
            themeMetadataService: $serviceManager->get(ThemesMetadataService::class),
            statsMetadataService: $serviceManager->get(StatsMetadataService::class)
        );
    }
}
