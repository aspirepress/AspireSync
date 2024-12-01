<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

interface RevisionMetadataServiceInterface
{
    public function setCurrentRevision(string $action, int $revision): void;

    public function preserveRevision(string $action): string;

    public function getRevisionForAction(string $action): ?string;

    public function getRevisionDateForAction(string $action): ?string;
}
