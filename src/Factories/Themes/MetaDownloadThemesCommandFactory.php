<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Themes;

use AspirePress\AspireSync\Commands\Themes\MetaDownloadThemesCommand;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use AspirePress\AspireSync\Services\WPEndpointClient;
use Laminas\ServiceManager\ServiceManager;

class MetaDownloadThemesCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): MetaDownloadThemesCommand
    {
        $listSerivce = $serviceManager->get(ThemeListService::class);
        $themesMeta = $serviceManager->get(ThemesMetadataService::class);
        $statsMeta   = $serviceManager->get(StatsMetadataService::class);
        $wpClient = $serviceManager->get(WPEndpointClient::class);
        return new MetaDownloadThemesCommand($listSerivce, $themesMeta, $statsMeta, $wpClient);
    }
}
