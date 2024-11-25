<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\List;

use AspirePress\AspireSync\Services\List\AbstractListService;
use AspirePress\AspireSync\Services\Metadata\ThemeMetadataService;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\SubversionService;

readonly class ThemeListService extends AbstractListService
{
    public function __construct(SubversionService $svn, ThemeMetadataService $meta, RevisionMetadataService $revisions)
    {
        parent::__construct($svn, $meta, $revisions, 'themes');
    }
}
