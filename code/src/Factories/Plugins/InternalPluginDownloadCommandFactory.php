<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Plugins;

use AspirePress\AspireSync\Commands\Plugins\InternalPluginDownloadCommand;
use AspirePress\AspireSync\Services\Plugins\PluginDownloadFromWpService;
use Laminas\ServiceManager\ServiceManager;

class InternalPluginDownloadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): InternalPluginDownloadCommand
    {
        $downloadService = $serviceManager->get(PluginDownloadFromWpService::class);
        return new InternalPluginDownloadCommand($downloadService);
    }
}
