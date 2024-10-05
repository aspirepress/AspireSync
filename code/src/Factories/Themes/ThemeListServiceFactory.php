<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Services\RevisionMetadataService;
use AssetGrabber\Services\Themes\ThemeListService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class ThemeListServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemeListService
    {
        $themeMetadata    = $serviceManager->get(ThemesMetadataService::class);
        $revisionMetadata = $serviceManager->get(RevisionMetadataService::class);
        return new ThemeListService($themeMetadata, $revisionMetadata);
    }
}
