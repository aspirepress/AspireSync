<?php

declare(strict_types=1);

namespace App\Tests\Stubs;

use App\Services\Interfaces\RevisionServiceInterface;

class RevisionServiceStub implements RevisionServiceInterface
{
    public function setCurrentRevision(string $key, int $revision): void
    {
        // de nada
    }

    public function preserveRevision(string $key): string
    {
        return "666";
    }

    public function getRevision(string $key): ?string
    {
        return null;
    }

    public function getRevisionDate(string $key): ?string
    {
        return null;
    }
}
