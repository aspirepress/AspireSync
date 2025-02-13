<?php

declare(strict_types=1);

namespace App\Services\Metadata;

use Generator;

interface MetadataServiceInterface
{
    public function exportAllMetadata(): Generator;

    /** @param array<string, mixed> $metadata */
    public function save(array $metadata, bool $clobber = false): void;

    /** @return array<string,int> */
    public function getPulledAfter(int $timestamp): array;

    /** @return array<string,int> */
    public function getCheckedAfter(int $timestamp): array;

    /** @return array<string|int, array{}> */
    public function getAllSlugs(): array;
}
