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

    public function status(string $slug): ?string;
}
