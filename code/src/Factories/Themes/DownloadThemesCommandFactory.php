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
        $metadata    = $serviceManager->get(ThemesMetadataService::class);
        $listService = $serviceManager->get(ThemeListService::class);
        $statsMeta   = $serviceManager->get(StatsMetadataService::class);

        return new DownloadThemesCommand($listService, $metadata, $statsMeta);
    }
}
