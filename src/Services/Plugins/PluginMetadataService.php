<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

use function Safe\json_encode;

readonly class PluginMetadataService extends AbstractMetadataService
{
    public function __construct(Connection $connection)
    {
        parent::__construct(connection: $connection, table: 'sync_plugins', files_table: 'sync_plugin_files');
    }

    /** @param array<string, mixed> $metadata */
    public function save(array $metadata): void
    {
        $this->connection->transactional(function () use ($metadata) {
            isset($metadata['error']) ? $this->saveErrorPlugin($metadata) : $this->saveOpenPlugin($metadata);
        });
    }

    /** @return array<string, string[]> */
    public function getOpenVersions(?string $revDate): array
    {
        $sql  = <<<SQL
            SELECT sync_plugins.id, slug, version, sync_plugin_files.metadata as version_meta 
            FROM sync_plugin_files 
                JOIN sync_plugins ON sync_plugins.id = sync_plugin_files.plugin_id 
            WHERE sync_plugins.status = 'open'
        SQL;
        $args = [];
        if ($revDate) {
            $sql            .= ' AND sync_plugins.pulled_at >= :revDate';
            $args['revDate'] = $revDate;
        }

        $result = $this->connection->fetchAllAssociative($sql, $args);
        $out    = [];
        foreach ($result as $row) {
            $plugin         = $row['slug'];
            $version        = $row['version'];
            $out[$plugin][] = $version;
        }
        return $out;
    }

    public function getDownloadUrl(string $slug, string $version): string
    {
        $sql = <<<'SQL'
            SELECT file_url
            FROM sync_plugin_files 
                JOIN sync_plugins ON sync_plugins.id = sync_plugin_files.plugin_id 
            WHERE sync_plugins.slug = :slug 
              AND sync_plugin_files.version = :version
        SQL;

        $result = $this->connection->fetchAssociative($sql, ['slug' => $slug, 'version' => $version]);
        return $result['file_url'];
    }

    public function setVersionToDownloaded(string $plugin, string $version): void {
        $sql = <<<'SQL'
            UPDATE sync_plugin_files 
            SET processed = current_timestamp 
            WHERE version = :version 
              AND plugin_id = (SELECT id FROM sync_plugins WHERE slug = :plugin)
            SQL;
        $this->connection->executeQuery($sql, ['plugin' => $plugin, 'version' => $version]);
    }

    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $slug, array $versions): array
    {
        $sql = <<<'SQL'
            SELECT version 
            FROM sync_plugin_files 
                JOIN sync_plugins ON sync_plugins.id = sync_plugin_files.plugin_id 
            WHERE sync_plugins.slug = :plugin 
              AND processed IS NULL 
              AND sync_plugin_files.version IN (:versions)
            SQL;

        $results = $this->connection->executeQuery(
            $sql,
            ['plugin' => $slug, 'versions' => $versions],
            ['versions' => ArrayParameterType::STRING]
        );
        return $results->fetchFirstColumn();
    }

    /**
     * @param array<int, string> $filterBy
     * @return string[]
     */
    public function getData(array $filterBy = []): array
    {
        if (! empty($filterBy)) {
            $sql     = "SELECT id, slug FROM sync_plugins WHERE status = 'open' AND slug IN (:plugins)";
            $plugins = $this->connection->fetchAllAssociative($sql, ['plugins' => $filterBy]);
        } else {
            $sql     = "SELECT id, slug FROM sync_plugins WHERE status = 'open'";
            $plugins = $this->connection->fetchAllAssociative($sql);
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
    public function getVersionData(string $pluginId, ?string $version = null): array|bool
    {
        $sql  = 'SELECT * FROM sync_plugin_files WHERE plugin_id = :plugin_id';
        $args = ['plugin_id' => $pluginId];
        if ($version) {
            $sql            .= ' AND version = :version';
            $args['version'] = $version;
        }

        return $this->connection->fetchAllAssociative($sql, $args);
    }

    public function getPulledDateTimestamp(string $slug): ?int
    {
        $sql    = "select unixepoch(pulled_at) timestamp from sync_plugins where slug = :slug";
        $result = $this->connection->fetchAssociative($sql, ['slug' => $slug]);
        if (! $result) {
            return null;
        }
        return (int) $result['timestamp'];
    }

    //region Private API

    /** @param array<string, mixed> $metadata */
    private function saveOpenPlugin(array $metadata): void
    {
        $id = Uuid::uuid7()->toString();

        $this->insertPlugin([
            'id'              => $id,
            'slug'            => $metadata['slug'],
            'name'            => mb_substr($metadata['name'], 0, 255),
            'current_version' => $metadata['version'],
            'status'          => 'open',
            'updated'         => date('c', strtotime($metadata['last_updated'])),
            'pulled_at'       => date('c'),
            'metadata'        => $metadata,
        ]);

        $versions = $metadata['versions'] ?: [$metadata['version'] => $metadata['download_link']];
        foreach ($versions as $version => $url) {
            $this->connection->insert('sync_plugin_files', [
                'id'        => Uuid::uuid7()->toString(),
                'plugin_id' => $id,
                'file_url'  => $url,
                'version'   => $version,
            ]);
        }
    }

    /** @param array<string, mixed> $metadata */
    private function saveErrorPlugin(array $metadata): void
    {
        if (! empty($metadata['closed_date'])) {
            $updated = date('c', strtotime($metadata['closed_date']));
        } else {
            $updated = date('c');
        }
        $this->insertPlugin([
            'id'        => Uuid::uuid7()->toString(),
            'name'      => substr($metadata['name'], 0, 255),
            'slug'      => $metadata['slug'],
            'updated'   => $updated,
            'pulled_at' => date('c'),
            'status'    => $metadata['status'] ?? 'error',
            'metadata'  => $metadata,
        ]);
    }

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

        $conn = $this->connection;

        $conn->delete('sync_plugins', ['slug' => $data['slug']]);
        $conn->insert('sync_plugins', $data);
    }

    //endregion
}
