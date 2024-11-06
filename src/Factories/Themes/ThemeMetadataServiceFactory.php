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
        return new ThemesMetadataService($serviceManager->get(ExtendedPdoInterface::class));
    }
}
