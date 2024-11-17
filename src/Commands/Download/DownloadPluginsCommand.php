<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Download;

use AspirePress\AspireSync\Services\List\PluginListService;
use AspirePress\AspireSync\Services\Metadata\PluginMetadataService;
use AspirePress\AspireSync\Services\ProcessManager;

class DownloadPluginsCommand extends AbstractDownloadCommand
{
    public function __construct(
        PluginListService $listService,
        PluginMetadataService $meta,
        ProcessManager $processManager,
    ) {
        parent::__construct($listService, $meta, $processManager, category: 'plugins');
    }
}
