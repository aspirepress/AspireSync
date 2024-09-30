<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\InternalDownloadThemesCommand;
use AssetGrabber\Services\ThemeDownloadService;
use Laminas\ServiceManager\ServiceManager;

ini_set('memory_limit', '2G');

class InternalDownloadThemesCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): InternalDownloadThemesCommand
    {
        $downloadService = $serviceManager->get(ThemeDownloadService::class);
        return new InternalDownloadThemesCommand($downloadService);
    }
}
