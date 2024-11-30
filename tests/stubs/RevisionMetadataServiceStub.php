<?php

declare(strict_types=1);

namespace App\Tests\Stubs;

use App\Services\Interfaces\RevisionMetadataServiceInterface;

class RevisionMetadataServiceStub implements RevisionMetadataServiceInterface
{
    public function setCurrentRevision(string $action, int $revision): void
    {
        // de nada
    }

    public function preserveRevision(string $action): string
    {
        return "666";
    }

    public function getRevisionForAction(string $action): ?string
    {
        return null;
    }

    public function getRevisionDateForAction(string $action): ?string
    {
        return null;
    }
}
