<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractDownloadCommand;
use AspirePress\AspireSync\Services\ProcessManager;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemeMetadataService;

class ThemesDownloadCommand extends AbstractDownloadCommand
{
    public function __construct(
        ThemeListService $listService,
        ThemeMetadataService $meta,
        ProcessManager $processManager,
    ) {
        parent::__construct($listService, $meta, $processManager);
    }

    protected function getCategory(): string
    {
        return 'themes';
    }
}
