<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\List;

use AspirePress\AspireSync\Services\Interfaces\SubversionServiceInterface;
use AspirePress\AspireSync\Services\List\AbstractListService;
use AspirePress\AspireSync\Services\Metadata\PluginMetadataService;
use AspirePress\AspireSync\Services\RevisionMetadataService;

readonly class PluginListService extends AbstractListService
{
    public function __construct(SubversionServiceInterface $svn, PluginMetadataService $meta, RevisionMetadataService $revisions)
    {
        parent::__construct($svn, $meta, $revisions, 'plugins');
    }
}
