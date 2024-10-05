<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Commands\Themes\ThemeImportMetaCommand;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class ThemeImportMetaCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemeImportMetaCommand
    {
        $metadataService = $serviceManager->get(ThemesMetadataService::class);
        return new ThemeImportMetaCommand($metadataService);
    }
}
