<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use Aura\Sql\ExtendedPdoInterface;
use Exception;
use PDOException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

use function Safe\json_decode;
use function Safe\json_encode;

class PluginMetadataService implements MetadataServiceInterface
{
    /** @var string[][] */
    private array $existing;

    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->existing = $this->loadExistingPlugins();
    }

    /** @param array<string, mixed> $meta */
    public function saveMetadata(array $meta): void
    {
        isset($meta['error']) ? $this->saveErrorPlugin($meta) : $this->saveOpenPlugin($meta);
    }

    /** @param array<string, mixed> $meta */
    public function saveErrorPlugin(array $meta): void
    {
        if (! empty($meta['closed_date'])) {
            $updated = date('c', strtotime($meta['closed_date']));
        } else {
            $updated = date('c');
        }
        $this->insertPlugin([
            'id'        => Uuid::uuid7()->toString(),
            'name'      => substr($meta['name'], 0, 255),
            'slug'      => $meta['slug'],
            'updated'   => $updated,
            'pulled_at' => date('c'),
            'status'    => $meta['status'] ?? 'error',
            'metadata'  => $meta,
        ]);
    }

    /** @param array<string, mixed> $meta */
    public function saveOpenPlugin(array $meta): void
    {
        $this->pdo->beginTransaction();

        $slug           = $meta['slug'];
        $currentVersion = $meta['version'];
        $versions       = $meta['versions'] ?: [$currentVersion => $meta['download_link']];
        $id             = Uuid::uuid7();

        $this->insertPlugin([
            'id'              => $id->toString(),
            'slug'            => $slug,
            'name'            => mb_substr($meta['name'], 0, 255),
            'current_version' => $currentVersion,
            'status'          => 'open',
            'updated'         => date('c', strtotime($meta['last_updated'])),
            'pulled_at'       => date('c'),
            'metadata'        => $meta,
        ]);

        $versionResult = $this->writeVersionsForPlugin($id, $versions, 'wp_cdn');

        if (! empty($versionResult['error'])) {
            throw new RuntimeException("Unable to write versions for plugin $slug");
        }

        $this->pdo->commit();
    }

    /**
     * @param string[] $versions
     * @return array|string[]
     */
    public function writeVersionsForPlugin(UuidInterface $pluginId, array $versions, string $cdn): array
    {
        $sql = <<<'SQL'
            INSERT INTO sync_plugin_files (id, plugin_id, file_url, type, version, created) 
            VALUES (:id, :plugin_id, :file_url, :type, :version, current_timestamp)
        SQL;

        if (! $this->pdo->inTransaction()) {
            $ourTransaction = true;
            $this->pdo->beginTransaction();
        }

        try {
            foreach ($versions as $version => $url) {
                $this->pdo->perform($sql, [
                    'id'        => Uuid::uuid7()->toString(),
                    'plugin_id' => $pluginId->toString(),
                    'file_url'  => $url,
                    'type'      => $cdn,
                    'version'   => $version,
                ]);
            }

            if (isset($ourTransaction)) {
                $this->pdo->commit();
            }

            return ['error' => ''];
        } catch (PDOException $e) {
            if (isset($ourTransaction)) {
                $this->pdo->rollBack();
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param array<int, string> $versions
     * @return array|string[]
     */
    public function writeVersionProcessed(UuidInterface $pluginId, array $versions, string $hash, string $cdn): array
    {
        $sql = <<<'SQL'
            INSERT INTO sync_plugin_files (id, plugin_id, file_url, type, version, created, processed, hash) 
            VALUES (:id, :plugin_id, :file_url, :type, :version, current_timestamp, current_timestamp, :hash)
            SQL;

        if (! $this->pdo->inTransaction()) {
            $ourTransaction = true;
            $this->pdo->beginTransaction();
        }

        try {
            foreach ($versions as $version => $url) {
                $this->pdo->perform($sql, [
                    'id'        => Uuid::uuid7()->toString(),
                    'plugin_id' => $pluginId->toString(),
                    'file_url'  => $url,
                    'type'      => $cdn,
                    'version'   => $version,
                    'hash'      => $hash,
                ]);
            }

            if (isset($ourTransaction)) {
                $this->pdo->commit();
            }

            return ['error' => ''];
        } catch (PDOException $e) {
            if (isset($ourTransaction)) {
                $this->pdo->rollBack();
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @return array|string[]
     */
    public function checkPluginInDatabase(string $slug): array
    {
        return $this->existing[$slug] ?? [];
    }

    /**
     * @param array<string, string|array<string, string>> $fileContents
     * @return array|string[]
     */
    public function updatePluginFromWP(array $fileContents, string $pulledAt): array
    {
        if (isset($fileContents['error']) && $fileContents['error'] === 'closed') {
            return $this->updateClosedPlugin($fileContents, $pulledAt);
        } else {
            return $this->updateOpenPlugin($fileContents, $pulledAt);
        }
    }

    /**
     * @param array<string, string|array<string, string>> $fileContents
     * @return array|string[]
     */
    private function updateClosedPlugin(array $fileContents, string $pulledAt): array
    {
        if (! empty($fileContents['closed_date'])) {
            $closedDate = date('c', strtotime($fileContents['closed_date']));
        } else {
            $closedDate = date('c');
        }
        try {
            $this->pdo->beginTransaction();

            $mdSql      = 'SELECT metadata FROM sync_plugins WHERE slug = :slug';
            $result     = $this->pdo->fetchOne($mdSql, ['slug' => $fileContents['slug']]);
            $metadata   = json_decode($result['metadata'], true);
            $apMetadata = $metadata['aspirepress_meta'];

            $newMetadata                     = $fileContents;
            $newMetadata['aspirepress_meta'] = [
                'seen'      => $apMetadata['seen'],
                'added'     => $apMetadata['added'],
                'updated'   => date('c'),
                'processed' => null,
                'finalized' => null,
            ];

            $sql = <<<'SQL'
                UPDATE sync_plugins 
                SET status = :status, 
                    pulled_at = :pulled_at, 
                    updated = :updated, 
                    metadata = :metadata 
                WHERE slug = :slug
                SQL;
            $this->pdo->perform(
                $sql,
                [
                    'status'    => 'closed',
                    'pulled_at' => $pulledAt,
                    'slug'      => $fileContents['slug'],
                    'updated'   => $closedDate,
                    'metadata'  => json_encode($newMetadata),
                ],
            );
            $this->pdo->commit();
            return ['error' => ''];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param array<string, string|array<string, string>> $fileContents
     * @return array|string[]
     */
    private function updateOpenPlugin(array $fileContents, string $pulledAt): array
    {
        $this->pdo->beginTransaction();

        try {
            $mdSql      = 'SELECT id, metadata FROM sync_plugins WHERE slug = :slug';
            $result     = $this->pdo->fetchOne($mdSql, ['slug' => $fileContents['slug']]);
            $metadata   = json_decode($result['metadata'], true);
            $id         = Uuid::fromString($result['id']);
            $apMetadata = $metadata['aspirepress_meta'];

            $newMetadata                     = $fileContents;
            $newMetadata['aspirepress_meta'] = [
                'seen'      => $apMetadata['seen'],
                'added'     => $apMetadata['added'],
                'updated'   => date('c'),
                'processed' => null,
                'finalized' => null,
            ];

            $name           = substr($fileContents['name'], 0, 255);
            $slug           = $fileContents['slug'];
            $currentVersion = $fileContents['version'];
            $versions       = $fileContents['versions'];
            $updatedAt      = date('c', strtotime($fileContents['last_updated']));

            $sql = <<<'SQL'
                UPDATE sync_plugins 
                SET metadata = :metadata, 
                    name = :name, 
                    current_version = :current_version, 
                    status = :status, 
                    updated = :updated, 
                    pulled_at = :pulled_at 
                WHERE slug = :slug
                SQL;
            $this->pdo->perform($sql, [
                'name'            => $name,
                'slug'            => $slug,
                'current_version' => $currentVersion,
                'status'          => 'open',
                'updated'         => $updatedAt,
                'pulled_at'       => $pulledAt,
                'metadata'        => json_encode($newMetadata),
            ]);

            if (empty($fileContents['versions'])) {
                $versions = [$fileContents['version'] => $fileContents['download_link']];
            }

            $newVersions = $this->getNewlyDiscoveredVersionsList($id->toString(), $versions);

            $versionResult = $this->writeVersionsForPlugin($id, $newVersions, 'wp_cdn');

            if (! empty($versionResult['error'])) {
                throw new RuntimeException('Unable to write versions for plugin ' . $slug);
            }

            $this->pdo->commit();
            return ['error' => ''];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param string[] $versions
     * @return string[]
     */
    private function getNewlyDiscoveredVersionsList(string $id, array $versions): array
    {
        $existingVersions = "SELECT version FROM sync_plugin_files WHERE type = 'wp_cdn' AND plugin_id = :id";
        $existingVersions = $this->pdo->fetchCol($existingVersions, ['id' => $id]);

        $newVersions = [];
        foreach ($versions as $version => $url) {
            if (! in_array($version, $existingVersions, true)) {
                $newVersions[$version] = $url;
            }
        }

        return $newVersions;
    }

    /**
     * @return array<string, string[]>
     */
    private function loadExistingPlugins(): array
    {
        $sql    = 'SELECT slug, status, pulled_at FROM sync_plugins';
        $result = [];
        foreach ($this->pdo->fetchAll($sql) as $row) {
            $result[$row['slug']] = ['status' => $row['status'], 'pulled_at' => $row['pulled_at']];
        }

        return $result;
    }

    /**
     * @return array<string, string[]>
     */
    public function getVersionsForUnfinalizedPlugins(?string $revDate, string $type = 'wp_cdn'): array
    {
        try {
            $notFound = $this->getNotFoundPlugins();
            $sql      = <<<SQL
                SELECT sync_plugins.id, slug, version, sync_plugin_files.metadata as version_meta 
                FROM sync_plugin_files 
                    LEFT JOIN sync_plugins ON sync_plugins.id = sync_plugin_files.plugin_id 
                WHERE sync_plugin_files.type = :type AND sync_plugins.status = 'open'
                SQL;
            $args     = ['type' => $type];
            if (! empty($revDate)) {
                $sql            .= ' AND sync_plugins.pulled_at >= :revDate';
                $args['revDate'] = $revDate;
            }

            $result      = $this->pdo->fetchAll($sql, $args);
            $finalResult = [];
            foreach ($result as $row) {
                $plugin  = $row['slug'];
                $version = $row['version'];
                if (! in_array($plugin, $notFound, true)) {
                    $finalResult[$plugin][] = $version;
                }
            }
            return $finalResult;
        } catch (PDOException $e) {
            throw new RuntimeException('Unable to get versions for plugins; reason: ' . $e->getMessage());
        }
    }

    public function getDownloadUrl(string $slug, string $version, string $type = 'wp_cdn'): string
    {
        $sql = <<<'SQL'
            SELECT file_url
            FROM sync_plugin_files 
                JOIN sync_plugins ON sync_plugins.id = sync_plugin_files.plugin_id 
            WHERE sync_plugins.slug = :slug 
              AND sync_plugin_files.version = :version
              AND sync_plugin_files.type = :type
        SQL;

        $result = $this->pdo->fetchOne($sql, ['slug' => $slug, 'type' => $type, 'version' => $version]);
        return $result['file_url'];
    }

    public function setVersionToDownloaded(
        string $plugin,
        string $version,
        ?string $hash = null,
        string $type = 'wp_cdn',
    ): void {
        $sql = <<<'SQL'
            UPDATE sync_plugin_files 
            SET processed = current_timestamp, 
                hash = :hash 
            WHERE version = :version 
              AND type = :type 
              AND plugin_id = (SELECT id FROM sync_plugins WHERE slug = :plugin)
            SQL;
        $this->pdo->perform($sql, ['plugin' => $plugin, 'type' => $type, 'hash' => $hash, 'version' => $version]);
    }

    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $slug, array $versions, string $type = 'wp_cdn'): array
    {
        $sql     = <<<'SQL'
            SELECT version 
            FROM sync_plugin_files 
                LEFT JOIN sync_plugins ON sync_plugins.id = sync_plugin_files.plugin_id 
            WHERE type = :type 
              AND sync_plugins.slug = :plugin 
              AND processed IS NULL 
              AND sync_plugin_files.version IN (:versions)
            SQL;
        $results = $this->pdo->fetchAll($sql, ['plugin' => $slug, 'type' => $type, 'versions' => $versions]);
        return array_map(fn ($row) => $row['version'], $results);
    }

    /**
     * @param array<int, string> $filterBy
     * @return string[]
     */
    public function getData(array $filterBy = []): array
    {
        if (! empty($filterBy)) {
            $sql     = "SELECT id, slug FROM sync_plugins WHERE status = 'open' AND slug IN (:plugins)";
            $plugins = $this->pdo->fetchAll($sql, ['plugins' => $filterBy]);
        } else {
            $sql     = "SELECT id, slug FROM sync_plugins WHERE status = 'open'";
            $plugins = $this->pdo->fetchAll($sql);
        }
        $result = [];
        foreach ($plugins as $plugin) {
            $result[$plugin['slug']] = $plugin['id'];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getVersionData(string $pluginId, ?string $version = null, string $type = 'wp_cdn'): array|bool
    {
        $sql  = 'SELECT * FROM sync_plugin_files WHERE plugin_id = :plugin_id AND type = :type';
        $args = [
            'plugin_id' => $pluginId,
            'type'      => $type,
        ];
        if ($version) {
            $sql            .= ' AND version = :version';
            $args['version'] = $version;
        }

        return $this->pdo->fetchAll($sql, $args);
    }

    /**
     * @return array<int, string>
     */
    public function getNotFoundPlugins(): array
    {
        $sql = "SELECT item_slug FROM sync_not_found_items WHERE item_type = 'plugin'";
        return $this->pdo->fetchCol($sql);
    }

    public function getStorageDir(): string
    {
        return '/opt/aspiresync/data/plugins';
    }

    public function getS3Path(): string
    {
        return '/plugins/';
    }

    public function getPulledDateTimestamp(string $slug): ?int
    {
        $sql    = "select unixepoch(pulled_at) timestamp from sync_plugins where slug = :slug";
        $result = $this->pdo->fetchOne($sql, ['slug' => $slug]);
        if (! $result) {
            return null;
        }
        return (int) $result['timestamp'];
    }

    //region Private API

    private function insertPlugin(array $data): void
    {
        $now              = date('c');
        $data['metadata'] = json_encode([
            ...$data['metadata'],
            'aspirepress_meta' => [
                'seen'      => $now,
                'added'     => $now,
                'updated'   => $now,
                'processed' => null,
                'finalized' => null,
            ],
        ]);

        $sql = <<<SQL
            INSERT OR REPLACE INTO sync_plugins (id, name, slug, status, updated, pulled_at, metadata) 
            VALUES (:id, :name, :slug, :status, :updated, :pulled_at, :metadata)
        SQL;
        $this->pdo->perform($sql, $data);
    }

    //endregion
}
