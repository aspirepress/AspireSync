<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use Doctrine\DBAL\Connection;
use Generator;

abstract readonly class AbstractMetadataService implements MetadataServiceInterface
{
    public function __construct(
        protected Connection $connection,
        protected string $table,
        protected string $files_table
    ) {
    }

    public function status(string $slug): ?string
    {
        $sql = "select status from $this->table where slug = ?";
        return $this->connection->fetchOne($sql, [$slug]) ?: null;

    }

    public function exportAllMetadata(): Generator
    {
        $sql  = "SELECT metadata FROM $this->table WHERE status = 'open'";
        $rows = $this->connection->executeQuery($sql);
        while ($row = $rows->fetchAssociative()) {
            yield $row['metadata'];
        }
    }
}
