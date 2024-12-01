<?php

declare(strict_types=1);

namespace App\Services;

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

    public function setCurrentRevision(string $action, int $revision): void
    {
        $this->currentRevision[$action] = ['revision' => $revision];
    }

    public function preserveRevision(string $action): string
    {
        if (!isset($this->currentRevision[$action])) {
            throw new RuntimeException('You did not specify a revision for action ' . $action);
        }
        $revision = $this->currentRevision[$action]['revision'];
        $this->connection->insert('revisions', ['action' => $action, 'revision' => $revision, 'created' => time()]);
        return (string) $revision;
    }

    public function getRevisionForAction(string $action): ?string
    {
        return $this->revisionData[$action]['revision'] ?? null;
    }

    public function getRevisionDateForAction(string $action): ?string
    {
        return $this->revisionData[$action]['added'] ?? null;
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
