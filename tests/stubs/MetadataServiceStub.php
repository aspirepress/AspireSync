<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Stubs;

use AspirePress\AspireSync\Services\Metadata\MetadataServiceInterface;
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

    public function getPulledAsTimestamp(string $slug): ?int
    {
        return 12345;
    }

    public function getOpenVersions(string $revDate = '1900-01-01'): array
    {
        return [];
    }

    public function markProcessed(string $slug, string $version): void
    {
        // nothingness
    }
}
