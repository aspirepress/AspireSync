<?php

namespace AspirePress\AspireSync\Services\Interfaces;

interface MetadataServiceInterface {

    public function getUnprocessedVersions(string $slug, array $versions, string $type = 'wp_cdn'): array;

    public function getDownloadUrl(string $slug, string $version, string $type = 'wp_cdn'): string;
}