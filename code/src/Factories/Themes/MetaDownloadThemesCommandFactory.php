<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Commands\Themes\MetaDownloadThemesCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use Laminas\ServiceManager\ServiceManager;

class MetaDownloadThemesCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaDownloadThemesCommand
    {
        $listSerivce = $serviceManager->get(ThemeListService::class);
        $statsMeta   = $serviceManager->get(StatsMetadataService::class);

        return new MetaDownloadThemesCommand($listSerivce, $statsMeta);
    }
}
