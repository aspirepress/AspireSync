<?php

declare(strict_types=1);

namespace App\Services\List;

use App\ResourceType;
use App\Services\Interfaces\SubversionServiceInterface;
use App\Services\Metadata\PluginMetadataService;
use App\Services\RevisionMetadataService;

readonly class PluginListService extends AbstractListService
{
    public function __construct(SubversionServiceInterface $svn, PluginMetadataService $meta, RevisionMetadataService $revisions)
    {
        parent::__construct($svn, $meta, $revisions, ResourceType::Plugin);
    }
}
