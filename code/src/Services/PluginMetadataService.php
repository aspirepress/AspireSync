<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use Aura\Sql\ExtendedPdoInterface;
use Exception;
use PDOException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

class PluginMetadataService
{
    /** @var string[][]  */
    private array $existing;
    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->existing = $this->loadExistingPlugins();
    }

    /**
     * @param  array<string, string|array<string, string>>  $pluginMetadata
     * @return array|string[]
     */
    public function saveClosedPluginFromWP(array $pluginMetadata, string $pulledAt): array
    {
        $sql = "INSERT INTO plugins (id, name, slug, status, updated, pulled_at, metadata) VALUES (:id, :name, :slug, :status, :closed_date, :pulled_at, :metadata)";

        if (! empty($pluginMetadata['closed_date'])) {
            $closedDate = date('c', strtotime($pluginMetadata['closed_date']));
        } else {
            $closedDate = date('c');
        }

        $pluginMetadata['aspirepress_meta'] = [
            'seen'      => date('c'),
            'added'     => date('c'),
            'updated'   => date('c'),
            'processed' => null,
            'finalized' => null,
        ];

        $this->pdo->beginTransaction();

        try {
            $this->pdo->perform($sql, [
                'id'          => Uuid::uuid7()->toString(),
                'name'        => substr($pluginMetadata['name'], 0, 255),
                'slug'        => $pluginMetadata['slug'],
                'closed_date' => $closedDate,
                'pulled_at'   => $pulledAt,
                'status'      => $pluginMetadata['error'],
                'metadata'    => json_encode($pluginMetadata),
            ]);
            $this->pdo->commit();
            return ['error' => ''];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, string|array<string, string>>  $pluginMetadata
     * @return array|string[]
     */
    public function saveOpenPluginFromWP(array $pluginMetadata, string $pulledAt): array
    {
        $this->pdo->beginTransaction();

        try {
            $name           = substr($pluginMetadata['name'], 0, 255);
            $slug           = $pluginMetadata['slug'];
            $currentVersion = $pluginMetadata['version'];
            $versions       = $pluginMetadata['versions'];
            $updatedAt      = date('c', strtotime($pluginMetadata['last_updated']));
            $id             = Uuid::uuid7();

            $pluginMetadata['aspirepress_meta'] = [
                'seen'      => date('c'),
                'added'     => date('c'),
                'updated'   => date('c'),
                'processed' => null,
                'finalized' => null,
            ];

            $sql = 'INSERT INTO plugins (id, name, slug, current_version, status, updated, pulled_at, metadata) VALUES (:id, :name, :slug, :current_version, :status, :updated_at, :pulled_at, :metadata)';
            $this->pdo->perform($sql, [
                'id'              => $id->toString(),
                'name'            => $name,
                'slug'            => $slug,
                'current_version' => $currentVersion,
                'status'          => 'open',
                'updated_at'      => $updatedAt,
                'pulled_at'       => $pulledAt,
                'metadata'        => json_encode($pluginMetadata),
            ]);

            if (empty($pluginMetadata['versions'])) {
                $versions[$pluginMetadata['version']] = $pluginMetadata['download_link'];
            } else {
                $versions = $pluginMetadata['versions'];
            }

            $versionResult = $this->writeVersionsForPlugin($id, $versions, 'wp_cdn');

            if (! empty($versionResult['error'])) {
                throw new Exception('Unable to write versions for plugin ' . $slug);
            }
            $this->pdo->commit();
            return ['error' => ''];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  string[]  $versions
     * @return array|string[]
     */
    public function writeVersionsForPlugin(UuidInterface $pluginId, array $versions, string $cdn): array
    {
        $sql = 'INSERT INTO plugin_files (id, plugin_id, file_url, type, version, metadata) VALUES (:id, :plugin_id, :file_url, :type, :version, :metadata)';

        if (! $this->pdo->inTransaction()) {
            $ourTransaction = true;
            $this->pdo->beginTransaction();
        }

        $metadata['aspirepress_meta'] = [
            'seen'      => date('c'),
            'added'     => date('c'),
            'updated'   => date('c'),
            'processed' => null,
            'finalized' => null,
        ];

        try {
            foreach ($versions as $version => $url) {
                $this->pdo->perform($sql, [
                    'id'        => Uuid::uuid7()->toString(),
                    'plugin_id' => $pluginId->toString(),
                    'file_url'  => $url,
                    'type'      => $cdn,
                    'version'   => $version,
                    'metadata'  => json_encode($metadata),
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
        if (isset($this->existing[$slug])) {
            return $this->existing[$slug];
        }

        return [];
    }

    /**
     * @param  array<string, string|array<string, string>>  $fileContents
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
     * @param  array<string, string|array<string, string>>  $fileContents
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

            $mdSql      = 'SELECT metadata FROM plugins WHERE slug = :slug';
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

            $sql = 'UPDATE plugins SET status = :status, pulled_at = :pulled_at, updated = :updated, metadata = :metadata WHERE slug = :slug';
            $this->pdo->perform(
                $sql,
                [
                    'status'    => 'closed',
                    'pulled_at' => $pulledAt,
                    'slug'      => $fileContents['slug'],
                    'updated'   => $closedDate,
                    'metadata'  => json_encode($newMetadata),
                ]
            );
            $this->pdo->commit();
            return ['error' => ''];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, string|array<string, string>>  $fileContents
     * @return array|string[]
     */
    private function updateOpenPlugin(array $fileContents, string $pulledAt): array
    {
        $this->pdo->beginTransaction();

        try {
            $mdSql      = 'SELECT id, metadata FROM plugins WHERE slug = :slug';
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

            $sql = 'UPDATE plugins SET metadata = :metadata, name = :name, current_version = :current_version, status = :status, updated = :updated, pulled_at = :pulled_at WHERE slug = :slug';
            $this->pdo->perform($sql, [
                'name'            => $name,
                'slug'            => $slug,
                'current_version' => $currentVersion,
                'status'          => 'open',
                'updated'         => $updatedAt,
                'pulled_at'       => $pulledAt,
                'metadata'        => json_encode($newMetadata),
            ]);

            if (! isset($fileContents['versions']) || empty($fileContents['versions'])) {
                $versions = [$fileContents['version'] => $fileContents['download_link']];
            } else {
                $versions = $fileContents['versions'];
            }

            $newVersions = $this->getNewlyDiscoveredVersionsList($id->toString(), $versions);

            $versionResult = $this->writeVersionsForPlugin($id, $newVersions, 'wp_cdn');

            if (! empty($versionResult['error'])) {
                throw new Exception('Unable to write versions for plugin ' . $slug);
            }

            $this->pdo->commit();
            return ['error' => ''];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  string[]  $versions
     * @return string[]
     */
    private function getNewlyDiscoveredVersionsList(string $id, array $versions): array
    {
        $existingVersions = 'SELECT version FROM plugin_files WHERE plugin_id = :id';
        $existingVersions = $this->pdo->fetchAll($existingVersions, ['id' => $id]);

        $newVersions = [];
        foreach ($versions as $version => $url) {
            if (! in_array($version, $existingVersions)) {
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
        $sql    = 'SELECT slug, status, pulled_at FROM plugins';
        $result = [];
        foreach ($this->pdo->fetchAll($sql) as $row) {
            $result[$row['slug']] = ['status' => $row['status'], 'pulled_at' => $row['pulled_at']];
        }

        return $result;
    }

    /**
     * @return array<string, string[]>
     */
    public function getVersionsForUnfinalizedPlugins(string $type = 'wp_cdn'): array
    {
        try {
            $sql         = 'SELECT plugins.id, slug, version, plugin_files.metadata as version_meta FROM plugin_files LEFT JOIN plugins ON plugins.id = plugin_files.plugin_id WHERE plugin_files.type = :type';
            $result      = $this->pdo->fetchAll($sql, ['type' => $type]);
            $finalResult = [];
            foreach ($result as $row) {
                $plugin  = $row['slug'];
                $version = $row['version'];
                if (! empty($row['metadata'])) {
                    $metadata = json_decode($row['metadata'], true);
                    if (! $metadata['aspirepress_meta']['finalized']) {
                        $finalResult[$plugin][] = $version;
                    }
                } else {
                    $finalResult[$plugin][] = $version;
                }
            }
            return $finalResult;
        } catch (PDOException $e) {
            throw new RuntimeException('Unable to get versions for plugins; reason: ' . $e->getMessage());
        }
    }

    /**
     * @param array<int, string> $versions
     * @return array<string, string>
     */
    public function getDownloadUrlsForVersions(string $plugin, array $versions, string $type = 'wp_cdn'): array
    {
        try {
            $sql = 'SELECT version, file_url FROM plugin_files LEFT JOIN plugins ON plugins.id = plugin_files.plugin_id WHERE plugins.slug = :plugin AND plugin_files.type = :type';

            $results = $this->pdo->fetchAll($sql, ['plugin' => $plugin, 'type' => $type]);
            $return  = [];
            foreach ($results as $result) {
                if (in_array($result['version'], $versions)) {
                    $return[$result['version']] = $result['file_url'];
                }
            }
            return $return;
        } catch (PDOException $e) {
            throw new RuntimeException('Unable to get download URLs for plugin ' . $plugin . '; reason: ' . $e->getMessage());
        }
    }

    public function setVersionToDownloaded(string $plugin, string $version, string $type = 'wp_cdn'): void
    {
        $sql    = 'SELECT plugin_files.id as id, plugin_files.metadata as metadata FROM plugin_files LEFT JOIN plugins ON plugins.id = plugin_files.plugin_id WHERE plugins.slug = :plugin AND plugin_files.type = :type AND plugin_files.version = :version';
        $result = $this->pdo->fetchOne($sql, ['plugin' => $plugin, 'type' => $type, 'version' => $version]);

        if (empty($result)) {
            return;
        }

        if (! empty($result['metadata'])) {
            $metadata                                  = json_decode($result['metadata'], true);
            $metadata['aspirepress_meta']['finalized'] = date('c');
        } else {
            $metadata = [
                'aspirepress_meta' => [
                    'finalized' => date('c'),
                ],
            ];
        }

        $sql = 'UPDATE plugin_files SET metadata = :metadata WHERE id = :id';
        $this->pdo->perform($sql, ['id' => $result['id'], 'metadata' => json_encode($metadata)]);
    }
}
