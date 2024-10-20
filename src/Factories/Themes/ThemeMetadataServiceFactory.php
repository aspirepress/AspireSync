<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
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
