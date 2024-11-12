<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\AbstractMetadataService;
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
        // status is something we add, and is the normalized error e.g. not-found
        // TODO: move status into aspiresync_meta and explicitly set it everywhere
        $status = $metadata['status'] ?? 'open';
        $method = match ($status) {
            'open'  => $this->saveOpenPlugin(...),
            default => $this->saveErrorPlugin(...),
        };
        $this->connection->transactional(fn () => $method($metadata));
    }

    /** @return array<string, string[]> [slug => [versions]] */
    public function getOpenVersions(string $revDate = '1900-01-01'): array
    {
        $sql  = <<<SQL
            SELECT sp.slug, spf.version 
            FROM sync_plugin_files spf
                JOIN sync_plugins sp ON sp.id = spf.plugin_id 
            WHERE sp.status = 'open'
              AND sp.pulled_at >= :revDate
        SQL;
        $result = $this->connection->fetchAllAssociative($sql, ['revDate' => $revDate]);

        $out = [];
        foreach ($result as $row) {
            $out[$row['slug']][] = $row['version'];
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

    public function setVersionToDownloaded(string $slug, string $version): void
    {
        $sql = <<<'SQL'
            UPDATE sync_plugin_files 
            SET processed = current_timestamp 
            WHERE version = :version 
              AND plugin_id = (SELECT id FROM sync_plugins WHERE slug = :slug)
            SQL;
        $this->connection->executeQuery($sql, ['slug' => $slug, 'version' => $version]);
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
        $this->insertPlugin([
            'id'              => Uuid::uuid7()->toString(),
            'slug'            => $metadata['slug'],
            'name'            => mb_substr($metadata['name'], 0, 255),
            'current_version' => null,
            'updated'         => $metadata['closed_date'] ?? date('c'),
            'pulled_at'       => date('c'),
            'status'          => $metadata['status'] ?? 'error',
            'metadata'        => $metadata,
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
