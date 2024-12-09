<?php

declare(strict_types=1);

namespace App\Services\List;

use App\ResourceType;
use App\Services\Interfaces\SubversionServiceInterface;
use App\Services\Metadata\MetadataServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

abstract class AbstractListService implements ListServiceInterface
{
    protected string $name;

    public function __construct(
        protected SubversionServiceInterface $svn,
        protected MetadataServiceInterface $meta,
        protected EntityManagerInterface $em,
        protected ResourceType $type,
        protected string $origin = 'wp_org',
    ) {
        $this->loadLatestRevisions();
    }

    //region Public API

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /** @return array<string|int, string[]> */
    public function getItems(): array
    {
        $lastRevision = $this->getRevision();
        return $lastRevision ? $this->getUpdatableItems($lastRevision) : $this->getAllSubversionSlugs();
    }

    /** @return array<string|int, array{}> */
    public function getUpdatedItems(): array
    {
        return $this->meta->getOpenVersions($this->getRevisionTime());
    }

    public function preserveRevision(): string
    {
        $name = $this->name;
        if (!isset($this->currentRevision[$name])) {
            throw new RuntimeException("No revision found for '$name'");
        }
        $revision = $this->currentRevision[$name]['revision'];
        $this->em->getConnection()->insert(
            'revisions',
            ['action' => $name, 'revision' => $revision, 'added' => time()]
        );
        return (string) $revision;
    }

    //endregion

    //region Protected API

    /** @return array<string|int, array{}> */
    protected function getAllSubversionSlugs(): array
    {
        $result = $this->svn->scrapeSlugsFromIndex($this->type);
        $this->setCurrentRevision($result['revision']);
        return $result['slugs'];
    }

    /** @return array<string, string[]> */
    protected function getUpdatableItems(string $lastRevision): array
    {
        $output   = $this->svn->getUpdatedSlugs($this->type, 0, (int) $lastRevision); // FIXME second arg should be prevRevision
        $revision = $output['revision'];
        $slugs    = $output['slugs'];
        $this->setCurrentRevision($revision);
        $new = array_diff_key($this->getAllSubversionSlugs(), $this->meta->getAllSlugs());
        return [...$slugs, ...$new];
    }

    //endregion

    //region RevisionService inlined

    /** @var array<string, array{revision:string, added:string}> */
    private array $revisionData = [];

    /** @var array<string, string[]> */
    private array $currentRevision = [];

    public function setCurrentRevision(int $revision): void
    {
        $this->currentRevision[$this->name] = ['revision' => $revision];
    }

    public function getRevision(): ?string
    {
        return $this->revisionData[$this->name]['revision'] ?? null;
    }

    public function getRevisionTime(): int
    {
        return $this->revisionData[$this->name]['added'] ?? 1;
    }

    private function loadLatestRevisions(): void
    {
        $sql = <<<SQL
                SELECT action, revision, added
                FROM (SELECT *, row_number() OVER (PARTITION by action ORDER BY added DESC) AS rownum FROM revisions) revs
                WHERE revs.rownum = 1;
            SQL;
        foreach ($this->em->getConnection()->fetchAllAssociative($sql) as $revision) {
            $this->revisionData[$revision['action']] = [
                'revision' => $revision['revision'],
                'added'    => $revision['added'],
            ];
        }
    }

    //endregion
}
