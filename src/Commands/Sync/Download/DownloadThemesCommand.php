<?php

declare(strict_types=1);

namespace App\Commands\Sync\Download;

use App\ResourceType;
use App\Services\Download\ThemeDownloadService;
use App\Services\List\ThemeListService;
use App\Services\Metadata\ThemeMetadataService;

class DownloadThemesCommand extends AbstractDownloadCommand
{
    public function __construct(
        ThemeListService $listService,
        ThemeMetadataService $meta,
        ThemeDownloadService $downloadService,
    ) {
        parent::__construct($listService, $meta, $downloadService, ResourceType::Theme);
    }
}
