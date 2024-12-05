<?php

declare(strict_types=1);

namespace App\Services\List;

use App\ResourceType;
use App\Services\Interfaces\RevisionServiceInterface;
use App\Services\Interfaces\SubversionServiceInterface;
use App\Services\Metadata\MetadataServiceInterface;

abstract readonly class AbstractListService implements ListServiceInterface
{
    protected string $name;

    public function __construct(
        protected SubversionServiceInterface $svn,
        protected MetadataServiceInterface $meta,
        protected RevisionServiceInterface $revisions,
        protected ResourceType $type,
        protected string $origin = 'wp_org',
    ) {
        $this->name = "list-{$type->plural()}@$origin";
    }

    //region Public API

    /**
     * @param string[] $filter
     * @return array<string|int, string[]>
     */
    public function getItems(array $filter, ?int $min_age = null): array
    {
        $lastRevision = $this->revisions->getRevision($this->name);
        $updates      = $lastRevision
            ? $this->getUpdatableItems($filter, $lastRevision)
            : $this->getAllSubversionSlugs();
        return $this->filter($updates, $filter, $min_age);
    }

    public function preserveRevision(): string
    {
        return $this->revisions->preserveRevision($this->name);
    }

    /** @return array<string|int, array{}> */
    public function getUpdatedItems(?array $requested): array
    {
        $revision = $this->revisions->getRevisionDate($this->name);
        if ($revision) {
            $revision = \Safe\date('Y-m-d', \Safe\strtotime($revision));
        }
        return $this->filter($this->meta->getOpenVersions($revision), $requested, null);
    }

    //endregion

    //region Protected API

    /** @return array<string|int, array{}> */
    protected function getAllSubversionSlugs(): array
    {
        $result = $this->svn->scrapeSlugsFromIndex($this->type);
        $this->revisions->setCurrentRevision($this->name, $result['revision']);
        return $result['slugs'];
    }

    /**
     * Reduces the items slated for update to only those specified in the filter.
     *
     * @param  array<string|int, array{}> $slugs
     * @param  string[]|null              $filter
     * @return array<string|int, array{}>
     */
    protected function filter(array $slugs, ?array $filter, ?int $min_age): array
    {
        if (!$filter && !$min_age) {
            return $slugs;
        }

        $filtered = $filter ? [] : $slugs;
        $filter ??= [];

        foreach ($filter as $slug) {
            if (array_key_exists($slug, $slugs)) {
                $filtered[$slug] = $slugs[$slug];
            }
        }
        if (!$min_age) {
            return $filtered;
        }

        $new = $this->meta->getPulledAfter(time() - $min_age);
        $out = [];
        foreach ($filtered as $slug => $value) {
            $slug = (string) $slug; // LOLPHP: php will turn any numeric string key into an int
            if ($new[$slug] ?? null) {
                continue;
            }
            $out[$slug] = $value;
        }
        return $out;
    }

    /**
     * Takes the entire list of items, and adds any we have not seen before,
     * plus merges items that we have explicitly queued for update.
     *
     * @param array<string|int, array{}> $update
     * @param string[] $requested
     * @return array<string|int, array{}>
     */
    protected function addNewAndRequested(array $update, ?array $requested): array
    {
        $all_slugs = $this->meta->getAllSlugs();
        $all_svn   = $this->getAllSubversionSlugs();

        foreach ($all_svn as $slug => $versions) {
            $slug = (string) $slug; // php will turn numeric keys into ints
            if (!array_key_exists($slug, $all_slugs) || in_array($slug, $requested, true)) {
                $update[$slug] = $versions;
            }
        }
        return $update;
    }

    /**
     * @param string[] $requested
     * @return array<string, string[]>
     */
    protected function getUpdatableItems(?array $requested, string $lastRevision): array
    {
        $output = $this->svn->getUpdatedSlugs($this->type, 0, (int) $lastRevision); // FIXME second arg should be prevRevision

        $revision = $output['revision'];
        $slugs    = $output['slugs'];

        $this->revisions->setCurrentRevision($this->name, $revision);
        return $this->addNewAndRequested($slugs, $requested);
    }

    //endregion
}
