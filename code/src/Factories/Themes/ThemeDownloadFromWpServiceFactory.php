<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Services\Themes\ThemeDownloadFromWpService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class ThemeDownloadFromWpServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemeDownloadFromWpService
    {
        $ua                = $serviceManager->get('config')['user-agents'];
        $themeMetadata = $serviceManager->get(ThemesMetadataService::class);
        return new ThemeDownloadFromWpService($ua, $themeMetadata);
    }
}
