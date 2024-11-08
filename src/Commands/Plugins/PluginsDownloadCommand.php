<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractDownloadCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\ProcessManager;
use AspirePress\AspireSync\Services\StatsMetadataService;

class PluginsDownloadCommand extends AbstractDownloadCommand
{
    public function __construct(
        PluginListService $listService,
        PluginMetadataService $meta,
        ProcessManager $processManager,
        StatsMetadataService $statsMeta,
    ) {
        parent::__construct($listService, $meta, $statsMeta, $processManager);
    }

    protected function getCategory(): string
    {
        return 'plugins';
    }
}
