<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Commands\Themes\InternalThemeDownloadCommand;
use AssetGrabber\Services\Themes\ThemeDownloadFromWpService;
use Laminas\ServiceManager\ServiceManager;

class InternalThemeDownloadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): InternalThemeDownloadCommand
    {
        $downloadService = $serviceManager->get(ThemeDownloadFromWpService::class);
        return new InternalThemeDownloadCommand($downloadService);
    }
}
