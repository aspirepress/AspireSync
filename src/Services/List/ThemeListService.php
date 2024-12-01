<?php

declare(strict_types=1);

namespace App\Services\List;

use App\Services\List\AbstractListService;
use App\Services\Metadata\ThemeMetadataService;
use App\Services\RevisionMetadataService;
use App\Services\SubversionService;

readonly class ThemeListService extends AbstractListService
{
    public function __construct(SubversionService $svn, ThemeMetadataService $meta, RevisionMetadataService $revisions)
    {
        parent::__construct($svn, $meta, $revisions, 'themes');
    }
}
