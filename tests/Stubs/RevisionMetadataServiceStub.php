<?php

declare(strict_types=1);

namespace App\Tests\Stubs;

use App\ResourceType;
use App\Services\Interfaces\RevisionMetadataServiceInterface;

class RevisionMetadataServiceStub implements RevisionMetadataServiceInterface
{
    public function setCurrentRevision(ResourceType $type, int $revision): void
    {
        // de nada
    }

    public function preserveRevision(ResourceType $type): string
    {
        return "666";
    }

    public function getRevisionForType(ResourceType $type): ?string
    {
        return null;
    }

    public function getRevisionDateForType(ResourceType $type): ?string
    {
        return null;
    }
}
