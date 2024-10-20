<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
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
