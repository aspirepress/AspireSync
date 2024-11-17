<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Download;

use AspirePress\AspireSync\Commands\Download\AbstractDownloadCommand;
use AspirePress\AspireSync\Services\ProcessManager;
use AspirePress\AspireSync\Services\List\ThemeListService;
use AspirePress\AspireSync\Services\Metadata\ThemeMetadataService;

class DownloadThemesCommand extends AbstractDownloadCommand
{
    public function __construct(
        ThemeListService $listService,
        ThemeMetadataService $meta,
        ProcessManager $processManager,
    ) {
        parent::__construct($listService, $meta, $processManager, category: 'themes');
    }
}
