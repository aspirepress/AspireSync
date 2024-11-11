<?php

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\ListServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\SubversionServiceInterface;

abstract class AbstractListService implements ListServiceInterface {
    public function __construct(
        protected readonly SubversionServiceInterface $svn,
        protected readonly MetadataServiceInterface $meta,
        protected readonly RevisionMetadataService $revisions,
    ) {
    }


}