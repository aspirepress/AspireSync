<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Generator;
use Ramsey\Uuid\Uuid;

use function Safe\json_decode;
use function Safe\json_encode;

abstract readonly class AbstractMetadataService implements MetadataServiceInterface
{
    public function __construct(
        protected Connection $connection,
        protected Resource $resource,
        protected string $origin = 'wp_org',
    ) {
    }

    /** @param array<string, mixed> $metadata */
    public function save(array $metadata): void
    {
        // status is something we add, and is the normalized error e.g. not-found
        $status = $metadata['status'] ?? 'open';
        $method = match ($status) {
            'open'  => $this->saveOpen(...),
            default => $this->saveError(...),
        };
        $this->connection->transactional(fn () => $method($metadata));
    }

    /** @param array<string, mixed> $metadata */
    protected function saveOpen(array $metadata): void
    {
        $id = Uuid::uuid7()->toString();

        $this->insertSync([
            'id'       => $id,
            'type'     => $this->resource->value,
            'slug'     => mb_substr($metadata['slug'], 0, 255),
            'name'     => mb_substr($metadata['name'], 0, 255),
            'status'   => 'open',
            'version'  => $metadata['version'],
            'origin'   => $this->origin,
            'updated'  => date('c', strtotime($metadata['last_updated'])),
            'pulled'   => date('c'),
            'metadata' => $metadata,
        ]);

        $versions = $metadata['versions'] ?: [$metadata['version'] => $metadata['download_link']];
        foreach ($versions as $version => $url) {
            $this->connection->insert('sync_assets', [
                'id'      => Uuid::uuid7()->toString(),
                'sync_id' => $id,
                'version' => $version,
                'url'     => $url,
            ]);
        }
    }

    /** @param array<string, mixed> $metadata */
    protected function saveError(array $metadata): void
    {
        $this->insertSync([
            'id'       => Uuid::uuid7()->toString(),
            'type'     => $this->resource->value,
            'slug'     => mb_substr($metadata['slug'], 0, 255),
            'name'     => mb_substr($metadata['name'], 0, 255),
            'status'   => $metadata['status'] ?? 'error',
            'version'  => null,
            'origin'   => $this->origin,
            'updated'  => $metadata['closed_date'] ?? date('c'),
            'pulled'   => date('c'),
            'metadata' => $metadata,
        ]);
    }

    // These getters are more efficient than using ->fetch()
    public function getStatus(string $slug): ?string
    {
        $sql = "select status from sync where slug = :slug and type = :type and origin = :origin";
        return $this->connection->fetchOne($sql, ['slug' => $slug, ...$this->stdArgs()]) ?: null;
    }

    public function getPulledAsTimestamp(string $slug): ?int
    {
        $sql    = "select unixepoch(pulled) from sync where slug = :slug and type = :type and origin = :origin";
        $pulled = $this->connection->fetchOne($sql, ['slug' => $slug, ...$this->stdArgs()]);
        return (int) $pulled ?: null;
    }

    public function getDownloadUrl(string $slug, string $version): ?string
    {
        $sql    = <<<'SQL'
            SELECT url
            FROM sync_assets 
                JOIN sync ON sync.id = sync_assets.sync_id 
            WHERE sync.slug = :slug 
              AND sync.type = :type
              AND sync.origin = :origin
              AND sync_assets.version = :version
        SQL;
        $result = $this->connection->fetchAssociative($sql, ['slug' => $slug, 'version' => $version, ...$this->stdArgs()]);
        return $result['url'] ?? null;
    }

    /** @return array<string, string[]> [slug => [versions]] */
    public function getOpenVersions(string $revDate = '1900-01-01'): array
    {
        $sql    = <<<SQL
            SELECT slug, sync_assets.version 
            FROM sync_assets
                JOIN sync ON sync.id = sync_assets.sync_id 
            WHERE status = 'open'
              AND pulled >= :revDate
              AND sync.type = :type
              AND sync.origin = :origin
        SQL;
        $result = $this->connection->fetchAllAssociative($sql, ['revDate' => $revDate, ...$this->stdArgs()]);

        $out = [];
        foreach ($result as $row) {
            $out[$row['slug']][] = $row['version'];
        }
        return $out;
    }

    public function markProcessed(string $slug, string $version): void
    {
        $sql = <<<'SQL'
            UPDATE sync_assets 
            SET processed = current_timestamp 
            WHERE version = :version
              AND sync_id = (SELECT id FROM sync WHERE slug = :slug AND type = :type AND origin = :origin)
            SQL;
        $this->connection->executeQuery($sql, ['slug' => $slug, 'version' => $version, $this->stdArgs()]);
    }

    public function exportAllMetadata(): Generator
    {
        $sql  = "select * from sync where status in ('open', 'closed') and type = :type and origin = :origin";
        $rows = $this->connection->executeQuery($sql, $this->stdArgs());
        while ($row = $rows->fetchAssociative()) {
            $metadata = json_decode($row['metadata'], true);
            unset($row['metadata']);
            $metadata['aspiresync_meta'] = $row;
            yield json_encode($metadata);
        }
    }

    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $slug, array $versions): array
    {
        $sql = <<<'SQL'
            select sync_assets.version 
            from sync_assets
                join sync on sync.id = sync_assets.sync_id 
            where sync.slug = :slug 
              and processed is null 
              and sync_assets.version in (:versions)
              and sync.type = :type
              and sync.origin = :origin
            SQL;

        $results = $this->connection->executeQuery(
            $sql,
            ['slug' => $slug, 'versions' => $versions, ...$this->stdArgs()],
            ['versions' => ArrayParameterType::STRING]
        );
        return $results->fetchFirstColumn();
    }

    /** @return array{type: string, origin: string} */
    protected function stdArgs(): array
    {
        return ['type' => $this->resource->value, 'origin' => $this->origin];
    }

    // keep fetch protected so we don't expose the raw db schema publicly
    /** @return array<string, string|array<string, mixed>>|null */
    protected function fetch(string $slug): ?array
    {
        $sql    = "select * from sync where slug = :slug and type = :type and origin = :origin";
        $params = ['slug' => $slug, ...$this->stdArgs()];
        $item   = $this->connection->fetchAssociative($sql, $params);
        return [
            'id'       => $item['id'],
            'type'     => $item['type'],
            'slug'     => $item['slug'],
            'name'     => $item['name'],
            'status'   => $item['status'],
            'version'  => $item['version'],
            'origin'   => $item['origin'],
            'updated'  => new DateTimeImmutable($item['updated']),
            'pulled'   => new DateTimeImmutable($item['pulled']),
            'metadata' => json_decode($item['metadata'] ?? 'null'),
        ];
    }

    /** @param array<string, mixed> $metadata */
    protected function insertSync(array $args): void
    {
        $args['metadata'] = json_encode($args['metadata']);
        $conn = $this->connection;
        $conn->delete('sync', ['slug' => $args['slug'], ...$this->stdArgs()]);
        $conn->insert('sync', $args);
    }
}
