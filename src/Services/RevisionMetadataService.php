<?php

declare(strict_types=1);

namespace App\Services;

use App\ResourceType;
use App\Services\Interfaces\RevisionMetadataServiceInterface;
use Doctrine\DBAL\Connection;
use RuntimeException;

class RevisionMetadataService implements RevisionMetadataServiceInterface
{
    /** @var array<string, array{revision:string, added:string}> */
    private array $revisionData = [];

    /** @var array<string, string[]> */
    private array $currentRevision = [];

    public function __construct(private Connection $connection)
    {
        $this->loadLatestRevisions();
    }

    public function setCurrentRevision(ResourceType $type, int $revision): void
    {
        $this->currentRevision[$type->value] = ['revision' => $revision];
    }

    public function preserveRevision(ResourceType $type): string
    {
        if (!isset($this->currentRevision[$type->value])) {
            throw new RuntimeException('You did not specify a revision for action ' . $type);
        }
        $revision = $this->currentRevision[$type->value]['revision'];
        $this->connection->insert('revisions', ['action' => $type, 'revision' => $revision, 'created' => time()]);
        return (string) $revision;
    }

    public function getRevisionForType(ResourceType $type): ?string
    {
        return $this->revisionData[$type->value]['revision'] ?? null;
    }

    public function getRevisionDateForType(ResourceType $type): ?string
    {
        return $this->revisionData[$type->value]['added'] ?? null;
    }

    private function loadLatestRevisions(): void
    {
        $sql = <<<SQL
            SELECT action, revision, added
            FROM (SELECT *, row_number() OVER (PARTITION by action ORDER BY added DESC) AS rownum FROM revisions) revs
            WHERE revs.rownum = 1;
            SQL;
        foreach ($this->connection->fetchAllAssociative($sql) as $revision) {
            $this->revisionData[$revision['action']] = [
                'revision' => $revision['revision'],
                'added'    => $revision['created'],
            ];
        }
    }
}
