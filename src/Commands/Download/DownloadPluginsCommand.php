<?php

declare(strict_types=1);

namespace App\Commands\Download;

use App\ResourceType;
use App\Services\Download\PluginDownloadService;
use App\Services\List\PluginListService;
use App\Services\Metadata\PluginMetadataService;

class DownloadPluginsCommand extends AbstractDownloadCommand
{
    public function __construct(
        PluginListService $listService,
        PluginMetadataService $meta,
        PluginDownloadService $downloadService,
    ) {
        parent::__construct($listService, $meta, $downloadService, ResourceType::Plugin);
    }
}
