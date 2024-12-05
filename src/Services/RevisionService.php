<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Interfaces\RevisionServiceInterface;
use Doctrine\DBAL\Connection;
use RuntimeException;

class RevisionService implements RevisionServiceInterface
{
    /** @var array<string, array{revision:string, added:string}> */
    private array $revisionData = [];

    /** @var array<string, string[]> */
    private array $currentRevision = [];

    public function __construct(private Connection $connection)
    {
        $this->loadLatestRevisions();
    }

    public function setCurrentRevision(string $key, int $revision): void
    {
        $this->currentRevision[$key] = ['revision' => $revision];
    }

    public function preserveRevision(string $key): string
    {
        if (!isset($this->currentRevision[$key])) {
            throw new RuntimeException("No revision found for '$key'");
        }
        $revision = $this->currentRevision[$key]['revision'];
        $this->connection->insert('revisions', ['action' => $key, 'revision' => $revision, 'created' => time()]);
        return (string) $revision;
    }

    public function getRevision(string $key): ?string
    {
        return $this->revisionData[$key]['revision'] ?? null;
    }

    public function getRevisionDate(string $key): ?string
    {
        return $this->revisionData[$key]['added'] ?? null;
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
