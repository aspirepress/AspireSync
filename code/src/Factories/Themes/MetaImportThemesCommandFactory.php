<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Commands\Themes\MetaImportThemesCommand;
use AssetGrabber\Services\StatsMetadataService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class MetaImportThemesCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaImportThemesCommand
    {
        $metadataService = $serviceManager->get(ThemesMetadataService::class);
        $statsMeta = $serviceManager->get(StatsMetadataService::class);

        return new MetaImportThemesCommand($metadataService, $statsMeta);
    }
}
