<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use Aura\Sql\ExtendedPdoInterface;

class RevisionMetadataService
{
    /** @var array<string, string[]> */
    private array $revisionData = [];

    /** @var array<string, string[]> */
    private array $currentRevision = [];
    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->loadRevisionData();
    }

    public function loadRevisionData(): void
    {
        $revisions = $this->pdo->fetchAll('SELECT * FROM revisions');
        foreach ($revisions as $revision) {
            $this->revisionData[$revision['action']] = ['id' => $revision['id'], 'revision' => $revision['revision']];
        }
    }

    public function setCurrentRevision(string $action, int $revision): void
    {
        $this->currentRevision[$action] = ['revision' => $revision];
    }

    public function preserveRevision(string $action): void
    {
        $data = [
            'id'       => $this->revisionData[$action]['id'] ?? null,
            'action'   => $action,
            'revision' => $this->currentRevision[$action]['revision'],
        ];

        if ($data['id'] === null) {
            $sql = 'INSERT INTO revisions (action, revision, added_at) VALUES (:action, :revision, NOW())';
            unset($data['id']);
        } else {
            $sql = 'UPDATE revisions SET revision = :revision, added_at = NOW() WHERE id = :id';
            unset($data['action']);
        }

        $this->pdo->perform($sql, $data);
    }

    public function getRevisionForAction(string $action): ?string
    {
        if (isset($this->revisionData[$action])) {
            return $this->revisionData[$action]['revision'];
        }

        return null;
    }
}
