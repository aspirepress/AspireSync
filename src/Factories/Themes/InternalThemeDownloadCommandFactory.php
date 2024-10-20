<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Commands\Themes\InternalThemeDownloadCommand;
use AspirePress\AspireSync\Services\Themes\ThemeDownloadFromWpService;
use Laminas\ServiceManager\ServiceManager;

class InternalThemeDownloadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): InternalThemeDownloadCommand
    {
        $downloadService = $serviceManager->get(ThemeDownloadFromWpService::class);
        return new InternalThemeDownloadCommand($downloadService);
    }
}
