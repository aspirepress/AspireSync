<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Themes;

use AssetGrabber\Services\Themes\ThemesMetadataService;
use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

class ThemeMetadataServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemesMetadataService
    {
        $pdo = $serviceManager->get(ExtendedPdoInterface::class);
        return new ThemesMetadataService($pdo);
    }
}