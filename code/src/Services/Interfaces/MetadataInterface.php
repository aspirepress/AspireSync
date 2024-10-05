<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Interfaces;

use Ramsey\Uuid\UuidInterface;

interface MetadataInterface
{
    /**
     * @param array<string, string|array<string, string>> $pluginMetadata
     * @return array|string[]
     */
    public function saveClosedPluginFromWP(array $pluginMetadata, string $pulledAt): array;

    /**
     * @param array<string, string|array<string, string>> $pluginMetadata
     * @return array|string[]
     */
    public function saveOpenPluginFromWP(array $pluginMetadata, string $pulledAt): array;

    /**
     * @param string[] $versions
     * @return array|string[]
     */
    public function writeVersionsForPlugin(UuidInterface $pluginId, array $versions, string $cdn): array;

    public function writeVersionProcessed(UuidInterface $pluginId, array $versions, string $cdn);

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
    public function getVersionsForUnfinalizedPlugins(string $type = 'wp_cdn'): array;

    /**
     * @param array<int, string> $versions
     * @return array<string, string>
     */
    public function getDownloadUrlsForVersions(string $plugin, array $versions, string $type = 'wp_cdn'): array;

    public function setVersionToDownloaded(string $plugin, string $version, string $type = 'wp_cdn'): void;

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

    public function getNotFoundPlugins(): array;

    public function isNotFound(string $item, bool $noLimit = false): bool;

    public function markItemNotFound(string $item): void;

    public function getStorageDir(): string;
}
