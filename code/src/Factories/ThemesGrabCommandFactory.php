<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\ThemesGrabCommand;
use AssetGrabber\Services\ThemeListService;
use Laminas\ServiceManager\ServiceManager;

class ThemesGrabCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemesGrabCommand
    {
        $themeService = $serviceManager->get(ThemeListService::class);
        return new ThemesGrabCommand($themeService);
    }
}
