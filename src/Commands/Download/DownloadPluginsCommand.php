<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Download;

use AspirePress\AspireSync\Services\Download\PluginDownloadService;
use AspirePress\AspireSync\Services\List\PluginListService;
use AspirePress\AspireSync\Services\Metadata\PluginMetadataService;

class DownloadPluginsCommand extends AbstractDownloadCommand
{
    public function __construct(
        PluginListService $listService,
        PluginMetadataService $meta,
        PluginDownloadService $downloadService,
    ) {
        parent::__construct($listService, $meta, $downloadService, category: 'plugins');
    }
}
