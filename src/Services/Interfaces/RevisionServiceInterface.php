<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\ResourceType;

interface RevisionServiceInterface
{
    public function setCurrentRevision(string $key, int $revision): void;

    public function preserveRevision(string $key): string;

    public function getRevision(string $key): ?string;

    public function getRevisionDate(string $key): ?string;
}
