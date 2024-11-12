<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\AbstractListService;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\SubversionService;

class ThemeListService extends AbstractListService
{
    protected int $prevRevision = 0;

    public function __construct(
        SubversionService $svn,
        ThemeMetadataService $meta,
        RevisionMetadataService $revisions,
    ) {
        parent::__construct($svn, $meta, $revisions, 'themes');
    }

    public function getUpdatedItems(?array $requested): array
    {
        $revision = $this->revisions->getRevisionDateForAction($this->category);
        if ($revision) {
            $revision = date('Y-m-d', strtotime($revision));
        }
        return $this->filter(
            $this->meta->getVersionsForUnfinalizedThemes($revision),
            $requested,
            null
        );
    }

}
