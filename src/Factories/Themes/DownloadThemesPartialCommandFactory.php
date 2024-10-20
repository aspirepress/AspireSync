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
        $listService = $serviceManager->get(ThemeListService::class);
        $metadata    = $serviceManager->get(ThemesMetadataService::class);
        $statsMeta   = $serviceManager->get(StatsMetadataService::class);

        return new DownloadThemesPartialCommand($listService, $metadata, $statsMeta);
    }
}
