<?php

declare(strict_types=1);

namespace App\Tests\Stubs;

use App\Services\Metadata\MetadataServiceInterface;
use Generator;

class MetadataServiceStub implements MetadataServiceInterface
{
    public function getUnprocessedVersions(string $slug, array $versions): array
    {
        return [];
    }

    public function getDownloadUrl(string $slug, string $version): ?string
    {
        return "https://example.org/download/$slug.$version";
    }

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

    public function getOpenVersions(?int $timestamp = 1): array
    {
        return [];
    }

    public function markProcessed(string $slug, string $version): void
    {
        // nothingness
    }

    public function getPulledAfter(int $timestamp): array
    {
        return [];
    }

    public function getAllSlugs(): array
    {
        return [];
    }
}
