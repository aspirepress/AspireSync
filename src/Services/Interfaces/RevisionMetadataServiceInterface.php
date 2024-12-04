<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\ResourceType;

interface RevisionMetadataServiceInterface
{
    public function setCurrentRevision(ResourceType $type, int $revision): void;

    public function preserveRevision(ResourceType $type): string;

    public function getRevisionForType(ResourceType $type): ?string;

    public function getRevisionDateForType(ResourceType $type): ?string;
}
