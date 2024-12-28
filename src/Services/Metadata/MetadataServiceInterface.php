<?php

declare(strict_types=1);

namespace App\Services\Metadata;

use Generator;

interface MetadataServiceInterface
{
    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $slug, array $versions): array;

    public function getDownloadUrl(string $slug, string $version): ?string;

    public function exportAllMetadata(): Generator;

    /** @param array<string, mixed> $metadata */
    public function save(array $metadata, bool $clobber = false): void;

    /** @return array<string|int, string[]> */
    public function getOpenVersions(int $timestamp = 1): array;

    public function markProcessed(string $slug, string $version): void;

    /** @return array<string,int> */
    public function getPulledAfter(int $timestamp): array;

    /** @return array<string|int, array{}> */
    public function getAllSlugs(): array;
}
