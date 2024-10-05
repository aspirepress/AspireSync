<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Commands\Themes\ThemesMetaCommand;
use AssetGrabber\Services\Themes\ThemeListService;
use Laminas\ServiceManager\ServiceManager;

class ThemeMetaCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemesMetaCommand
    {
        $listSerivce = $serviceManager->get(ThemeListService::class);
        return new ThemesMetaCommand($listSerivce);
    }
}