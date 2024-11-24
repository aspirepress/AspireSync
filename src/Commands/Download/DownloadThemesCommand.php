<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Download;

use AspirePress\AspireSync\Commands\Download\AbstractDownloadCommand;
use AspirePress\AspireSync\ResourceType;
use AspirePress\AspireSync\Services\Download\ThemeDownloadService;
use AspirePress\AspireSync\Services\List\ThemeListService;
use AspirePress\AspireSync\Services\Metadata\ThemeMetadataService;

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
