<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

use Ramsey\Uuid\UuidInterface;

interface MetadataInterface
{
    /** @param array<string, string|array<string, string>> $meta */
    public function saveErrorPlugin(array $meta): void;

    /** @param array<string, string|array<string, string>> $meta */
    public function saveOpenPlugin(array $meta): void;

    /**
     * @param string[] $versions
     * @return array|string[]
     */
    public function writeVersionsForPlugin(UuidInterface $pluginId, array $versions, string $cdn): array;

    /**
     * @param  array<int, string>  $versions
     * @return array<string, string>
     */
    public function writeVersionProcessed(UuidInterface $pluginId, array $versions, string $hash, string $cdn): array;

    /**
     * @return array|string[]
     */
    public function checkPluginInDatabase(string $slug): array;

    /**
     * @param array<string, string|array<string, string>> $fileContents
     * @return array|string[]
     */
    public function updatePluginFromWP(array $fileContents, string $pulledAt): array;

    /**
     * @return array<string, string[]>
     */
    public function getVersionsForUnfinalizedPlugins(?string $revDate, string $type = 'wp_cdn'): array;

    /**
     * @param array<int, string> $versions
     * @return array<string, string>
     */
    public function getDownloadUrlsForVersions(string $plugin, array $versions, string $type = 'wp_cdn'): array;

    public function setVersionToDownloaded(string $plugin, string $version, ?string $hash = null, string $type = 'wp_cdn'): void;

    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $plugin, array $versions, string $type = 'wp_cdn'): array;

    /**
     * @param array<int, string> $filterBy
     * @return string[]
     */
    public function getData(array $filterBy = []): array;

    /**
     * @return string[]
     */
    public function getVersionData(string $pluginId, ?string $version, string $type = 'wp_cdn'): array|bool;

    /**
     * @return array<int, string>
     */
    public function getNotFoundPlugins(): array;

    public function isNotFound(string $item, bool $noLimit = false): bool;

    public function markItemNotFound(string $item): void;

    public function getStorageDir(): string;
}
