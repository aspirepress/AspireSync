<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use GuzzleHttp\Client as GuzzleClient;
use Laminas\ServiceManager\ServiceManager;

class ThemeListServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemeListService
    {
        return new ThemeListService(
            themesMetadataService: $serviceManager->get(ThemesMetadataService::class),
            revisionService: $serviceManager->get(RevisionMetadataService::class),
            guzzle: $serviceManager->get(GuzzleClient::class)
        );
    }
}
