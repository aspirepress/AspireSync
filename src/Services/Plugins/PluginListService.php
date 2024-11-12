<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\AbstractListService;
use AspirePress\AspireSync\Services\Interfaces\SubversionServiceInterface;
use AspirePress\AspireSync\Services\RevisionMetadataService;

readonly class PluginListService extends AbstractListService
{
    public function __construct(SubversionServiceInterface $svn, PluginMetadataService $meta, RevisionMetadataService $revisions) {
        parent::__construct($svn, $meta, $revisions, 'plugins');
    }
}
