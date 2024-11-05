<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Commands\Themes\ThemesMetaCommand;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use AspirePress\AspireSync\Services\WPEndpointClient;
use Laminas\ServiceManager\ServiceManager;

class ThemesMetaCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): ThemesMetaCommand
    {
        $listSerivce = $serviceManager->get(ThemeListService::class);
        $themesMeta = $serviceManager->get(ThemesMetadataService::class);
        $statsMeta   = $serviceManager->get(StatsMetadataService::class);
        $wpClient = $serviceManager->get(WPEndpointClient::class);
        return new ThemesMetaCommand($listSerivce, $themesMeta, $statsMeta, $wpClient);
    }
}
