<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\AbstractListService;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\SubversionService;

readonly class ThemeListService extends AbstractListService
{
    public function __construct(SubversionService $svn, ThemeMetadataService $meta, RevisionMetadataService $revisions)
    {
        parent::__construct($svn, $meta, $revisions, 'themes');
    }
}
