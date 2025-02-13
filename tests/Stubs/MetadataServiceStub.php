<?php

declare(strict_types=1);

namespace App\Tests\Stubs;

use App\Services\Metadata\MetadataServiceInterface;
use Generator;

class MetadataServiceStub implements MetadataServiceInterface
{
    public function exportAllMetadata(): Generator
    {
        yield from [];
    }

    public function save(array $metadata): void
    {
        // de nada
    }

    public function getStatus(string $slug): ?string
    {
        return 'open';
    }

    public function getPulledAfter(int $timestamp): array
    {
        return [];
    }

    public function getCheckedAfter(int $timestamp): array
    {
        return [];
    }

    public function getAllSlugs(): array
    {
        return [];
    }
}
