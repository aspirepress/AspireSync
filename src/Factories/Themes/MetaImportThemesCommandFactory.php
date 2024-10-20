<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Commands\Themes\MetaImportThemesCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class MetaImportThemesCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaImportThemesCommand
    {
        $metadataService = $serviceManager->get(ThemesMetadataService::class);
        $statsMeta       = $serviceManager->get(StatsMetadataService::class);

        return new MetaImportThemesCommand($metadataService, $statsMeta);
    }
}
