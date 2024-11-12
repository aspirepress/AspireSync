<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\AbstractListService;
use AspirePress\AspireSync\Services\Interfaces\SubversionServiceInterface;
use AspirePress\AspireSync\Services\RevisionMetadataService;

class PluginListService extends AbstractListService
{
    protected int $prevRevision = 0;

    public function __construct(
        SubversionServiceInterface $svn,
        PluginMetadataService $meta,
        RevisionMetadataService $revisions,
    ) {
        parent::__construct($svn, $meta, $revisions, 'plugins');
    }

    /**
     * TODO: port getOpenVersions to ThemeMetadataService and move this into the base class
     *
     * @param string[] $requested
     * @return array<string, string[]>
     */
    public function getUpdatedItems(?array $requested): array
    {
        $action ??= $this->category;
        $revDate = $this->revisions->getRevisionDateForAction($action);
        if ($revDate) {
            $revDate = date('Y-m-d', strtotime($revDate));
        }
        return $this->filter($this->meta->getOpenVersions($revDate), $requested, null);
    }
}
