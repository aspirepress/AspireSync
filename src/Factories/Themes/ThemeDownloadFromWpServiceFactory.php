<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Services\Themes\ThemeDownloadFromWpService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use GuzzleHttp\Client as GuzzleClient;
use Laminas\ServiceManager\ServiceManager;

class ThemeDownloadFromWpServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemeDownloadFromWpService
    {
        $themeMetadata = $serviceManager->get(ThemesMetadataService::class);
        $guzzle        = $serviceManager->get(GuzzleClient::class);

        return new ThemeDownloadFromWpService($themeMetadata, $guzzle);
    }
}
