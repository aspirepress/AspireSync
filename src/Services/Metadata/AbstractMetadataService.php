<?php

declare(strict_types=1);

namespace App\Services\Metadata;

use App\ResourceType;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Generator;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\strtotime;

abstract readonly class AbstractMetadataService implements MetadataServiceInterface
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected LoggerInterface $log,
        protected ResourceType $resource,
        protected string $origin = 'wp_org',
    ) {}

    //region Public API

    /** @param array<string, mixed> $metadata */
    public function save(array $metadata, bool $clobber = false): void
    {
        // status is something we add, and is the normalized error e.g. not-found
        $status = $metadata['status'] ?? 'open';
        $method = match ($status) {
            'open' => $this->saveOpen(...),
            default => $this->saveError(...),
        };
        $this->connection()->transactional(fn() => $method($metadata, $clobber));
    }

    /** @param array<string, mixed> $metadata */
    protected function saveOpen(array $metadata, bool $clobber): bool
    {
        $id = Uuid::uuid7()->toString();
        $slug = mb_substr($metadata['slug'], 0, 255);
        $version = mb_substr((string) $metadata['version'], 0, 32);

        if (!$clobber && $this->slugAndVersionExists($slug, $version)) {
            $this->log->debug("Not updating unmodified {$this->resource->value}", compact('slug', 'version'));
            return false;
        }

        $this->insertSync([
            'id' => $id,
            'type' => $this->resource->value,
            'slug' => $slug,
            'name' => mb_substr($metadata['name'], 0, 255),
            'status' => 'open',
            'version' => $version,
            'origin' => $this->origin,
            'updated' => strtotime($metadata['last_updated'] ?? 'now'),
            'pulled' => time(),
            'checked' => time(),
            'metadata' => $metadata,
        ]);

        $versions = $metadata['versions'] ?: [$metadata['version'] => $metadata['download_link']];
        foreach ($versions as $version => $url) {
            $this->connection()->insert('sync_assets', [
                'id' => Uuid::uuid7()->toString(),
                'sync_id' => $id,
                'version' => mb_substr((string) $version, 0, 32),
                'url' => mb_substr($url, 0, 4096),
                'created' => time(),
            ]);
        }

        $slug = $metadata['slug'];
        $type = $this->resource->value;
        $status = 'open';
        $version_count = count($versions);
        $this->log->debug("saved $type: $slug", compact('slug', 'type', 'status', 'version_count', 'id'));
        return true;
    }

    /** @param array<string, mixed> $metadata */
    protected function saveError(array $metadata, bool $clobber): bool
    {
        $closed = $metadata['closed'] ?? false;
        $status = $closed ? 'closed' : $metadata['status'] ?? 'error';
        $slug = mb_substr($metadata['slug'], 0, 255);

        if (!$clobber && $this->slugAndStatusExists($slug, $status)) {
            $this->log->debug("Not updating closed {$this->resource->value}", compact('slug', 'status'));
            return false;
        }

        $id = Uuid::uuid7()->toString();
        $type = $this->resource->value;
        $name = mb_substr($metadata['name'], 0, 255);
        $version = null;
        $origin = $this->origin;
        $updated = strtotime($metadata['closed_date'] ?? 'now');
        $pulled = time();
        $checked = time();

        $row = compact(
            'id',
            'type',
            'slug',
            'name',
            'status',
            'version',
            'origin',
            'updated',
            'pulled',
            'checked',
            'metadata',
        );
        $this->insertSync($row);
        $this->log->debug("saved $type: $slug", compact('slug', 'type', 'status', 'id'));
        return true;
    }

    /** @return array<string,int> */
    public function getPulledAfter(int $timestamp): array
    {
        return $this
            ->querySync()
            ->select('slug', 'pulled')
            ->andWhere('pulled > :timestamp')
            ->setParameter('timestamp', $timestamp)
            ->executeQuery()
            ->fetchAllKeyValue();
    }

    /** @return array<string,int> */
    public function getCheckedAfter(int $timestamp): array
    {
        return $this
            ->querySync()
            ->select('slug', 'checked')
            ->andWhere('checked > :timestamp')
            ->setParameter('timestamp', $timestamp)
            ->executeQuery()
            ->fetchAllKeyValue();
    }

    public function getDownloadUrl(string $slug, string $version): ?string
    {
        return $this
            ->querySyncAssets($slug)
            ->select('url')
            ->where('sync_assets.version = :version')
            ->setParameter('version', $version)
            ->fetchOne();
    }

    /** @return array<string, string[]> [slug => [versions]] */
    public function getOpenVersions(int $timestamp = 1): array
    {
        $sql = <<<SQL
            SELECT slug, sync_assets.version 
            FROM sync_assets
                JOIN sync ON sync.id = sync_assets.sync_id 
            WHERE status = 'open'
              AND pulled >= :stamp
              AND sync.type = :type
              AND sync.origin = :origin
            SQL;
        $result = $this->connection()->fetchAllAssociative($sql, ['stamp' => $timestamp, ...$this->stdArgs()]);

        $out = [];
        foreach ($result as $row) {
            $out[$row['slug']][] = $row['version'];
        }
        return $out;
    }

    public function getAllSlugs(): array
    {
        return $this->querySync()->select('slug')->executeQuery()->fetchFirstColumn() ?: [];
    }

    public function markProcessed(string $slug, string $version): void
    {
        $sql = <<<'SQL'
            UPDATE sync_assets 
            SET processed = :stamp 
            WHERE version = :version
              AND sync_id = (SELECT id FROM sync WHERE slug = :slug AND type = :type AND origin = :origin)
            SQL;
        $this->connection()->executeQuery(
            $sql,
            ['stamp' => time(), 'slug' => $slug, 'version' => $version, ...$this->stdArgs()],
        );
    }

    public function exportAllMetadata(int $after = 0): Generator
    {
        $query = $this->querySync()->select('*');
        $after > 0 and $query->andWhere('pulled > :after')->setParameter('after', $after);
        $rows = $query->executeQuery();
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

        $results = $this->connection()->executeQuery(
            $sql,
            ['slug' => $slug, 'versions' => $versions, ...$this->stdArgs()],
            ['versions' => ArrayParameterType::STRING],
        );
        return $results->fetchFirstColumn();
    }

    //endregion

    //region Protected/Private API

    /** @return array{type: string, origin: string} */
    protected function stdArgs(): array
    {
        return ['type' => $this->resource->value, 'origin' => $this->origin];
    }

    protected function querySync(): QueryBuilder
    {
        return $this
            ->connection()->createQueryBuilder()
            ->select('*')
            ->from('sync')
            ->andWhere('type = :type')
            ->andWhere('origin = :origin')
            ->setParameter('type', $this->resource->value)
            ->setParameter('origin', $this->origin);
    }

    protected function querySyncAssets(string $slug): QueryBuilder
    {
        return $this
            ->connection()->createQueryBuilder()
            ->select('*')
            ->from('sync_assets')
            ->andWhere(
                'sync_assets.sync_id = (SELECT id FROM sync WHERE slug = :slug AND type = :type AND origin = :origin)',
            )
            ->setParameter('slug', $slug)
            ->setParameter('type', $this->resource->value)
            ->setParameter('origin', $this->origin);
    }

    /** @param array<string, mixed> $args */
    protected function insertSync(array $args): void
    {
        $args['metadata'] = json_encode($args['metadata']);
        $conn = $this->connection();
        $conn->delete('sync', ['slug' => $args['slug'], ...$this->stdArgs()]);
        $conn->insert('sync', $args);
    }

    private function connection(): Connection
    {
        return $this->em->getConnection();
    }

    private function slugAndVersionExists(string $slug, string $version): bool
    {
        // update checked timestamp
        $this
            ->connection()
            ->update(
                'sync',
                ['checked' => time()],
                ['slug' => $slug, 'version' => $version, ...$this->stdArgs()],
            );

        return $this
                ->querySync()
                ->select('1')
                ->andWhere('slug = :slug')
                ->andWhere('version = :version')
                ->setParameter('slug', $slug)
                ->setParameter('version', $version)
                ->executeQuery()
                ->fetchOne() !== false;
    }

    private function slugAndStatusExists(string $slug, string $status): bool
    {
        $this->connection()->update(
            'sync',
            ['checked' => time()],
            ['slug' => $slug, 'status' => $status, ...$this->stdArgs()],
        );

        return $this
                ->querySync()
                ->select('1')
                ->andWhere('slug = :slug')
                ->andWhere('status = :status')
                ->setParameter('slug', $slug)
                ->setParameter('status', $status)
                ->executeQuery()
                ->fetchOne() !== false;
    }

    //endregion
}
