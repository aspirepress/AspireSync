<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Services\Themes\ThemeDownloadFromWpService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class ThemeDownloadFromWpServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemeDownloadFromWpService
    {
        $ua            = $serviceManager->get('config')['user-agents'];
        $themeMetadata = $serviceManager->get(ThemesMetadataService::class);
        return new ThemeDownloadFromWpService($ua, $themeMetadata);
    }
}
