<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

use Generator;

interface MetadataServiceInterface
{
    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $slug, array $versions): array;

    public function getDownloadUrl(string $slug, string $version): string;

    public function exportAllMetadata(): Generator;

    /** @param array<string, mixed> $metadata */
    public function save(array $metadata): void;

    public function getStatus(string $slug): ?string;

    public function getPulledAsTimestamp(string $slug): ?int;

    /** @return array<string, string[]> */
    public function getOpenVersions(string $revDate = '1900-01-01'): array;
}
