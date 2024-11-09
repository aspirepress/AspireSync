<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use Aura\Sql\ExtendedPdoInterface;
use RuntimeException;

class RevisionMetadataService
{
    /** @var array<string, array{revision:string, added:string}> */
    private array $revisionData = [];

    /** @var array<string, string[]> */
    private array $currentRevision = [];

    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->loadLatestRevisions();
    }

    public function setCurrentRevision(string $action, int $revision): void
    {
        $this->currentRevision[$action] = ['revision' => $revision];
    }

    public function preserveRevision(string $action): string
    {
        if (! isset($this->currentRevision[$action])) {
            throw new RuntimeException('You did not specify a revision for action ' . $action);
        }
        $revision = $this->currentRevision[$action]['revision'];
        $sql      = 'INSERT INTO sync_revisions (action, revision, added_at) VALUES (:action, :revision, current_timestamp)';
        $this->pdo->perform($sql, ['action' => $action, 'revision' => $revision]);
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

    /**
     * @return array<string, array{revision:string, added:string}>
     */
    public function getRevisionData(): array
    {
        return $this->revisionData;
    }

    private function loadLatestRevisions(): void
    {
        $sql = <<<SQL
            SELECT action, revision, added_at
            FROM (SELECT *, row_number() OVER (PARTITION by action ORDER BY added_at DESC) AS rownum FROM sync_revisions) revs
            WHERE revs.rownum = 1;
            SQL;
        foreach ($this->pdo->fetchAll($sql) as $revision) {
            $this->revisionData[$revision['action']] = [
                'revision' => $revision['revision'],
                'added'    => $revision['added_at'],
            ];
        }
    }
}
