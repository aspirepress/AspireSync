<?php

declare(strict_types=1);

namespace App\Services\List;

use App\ResourceType;
use App\Services\Interfaces\RevisionMetadataServiceInterface;
use App\Services\Interfaces\SubversionServiceInterface;
use App\Services\Metadata\MetadataServiceInterface;

abstract readonly class AbstractListService implements ListServiceInterface
{
    public function __construct(
        protected SubversionServiceInterface $svn,
        protected MetadataServiceInterface $meta,
        protected RevisionMetadataServiceInterface $revisions,
        protected ResourceType $type,
    ) {}

    /**
     * @param string[] $filter
     * @return array<string, string[]>
     */
    public function getItems(array $filter, ?int $min_age = null): array
    {
        $lastRevision = $this->revisions->getRevisionForType($this->type);
        $updates      = $lastRevision
            ? $this->getUpdatableItems($filter, $lastRevision)
            : $this->getAllSubversionSlugs();
        return $this->filter($updates, $filter, $min_age);
    }

    public function preserveRevision(): string
    {
        return $this->revisions->preserveRevision($this->type);
    }

    /**
     * @return array<string|int, string[]>
     *
     * LOLPHP: should always return array<string, string[]> but numeric keys like '1976' get forcibly cast to int when read.
     */
    protected function getAllSubversionSlugs(): array
    {
        $result = $this->svn->scrapeSlugsFromIndex($this->type);
        $this->revisions->setCurrentRevision($this->type, $result['revision']);

        // transform to [slug => [versions]] format
        $out = [];
        foreach ($result['slugs'] as $slug) {
            $out[$slug] = [];
        }
        return $out;
    }

    public function getUpdatedItems(?array $requested): array
    {
        $revision = $this->revisions->getRevisionDateForType($this->type);
        if ($revision) {
            $revision = \Safe\date('Y-m-d', \Safe\strtotime($revision));
        }
        return $this->filter($this->meta->getOpenVersions($revision), $requested, null);
    }

    //region Protected API

    /**
     * Reduces the items slated for update to only those specified in the filter.
     *
     * @param  array<string, string[]>  $items
     * @param  array<int, string>|null  $filter
     * @return array<string, string[]>
     */
    protected function filter(array $items, ?array $filter, ?int $min_age): array
    {
        if (!$filter && !$min_age) {
            return $items;
        }

        $filtered = $filter ? [] : $items;

        foreach ($filter as $slug) {
            if (array_key_exists($slug, $items)) {
                $filtered[$slug] = $items[$slug];
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
     * @param array<string, string[]> $update
     * @param string[] $requested
     * @return array<string, string[]>
     */
    protected function addNewAndRequested(array $update, ?array $requested): array
    {
        $allSlugs = $this->getAllSubversionSlugs();

        foreach ($allSlugs as $slug => $versions) {
            $slug   = (string) $slug;
            $status = $this->meta->getStatus($slug);
            // Is this the first time we've seen the slug?
            if (!$status) {
                $update[$slug] = [];
            }
            if (in_array($slug, $requested, true)) {
                $update[$slug] = [];
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

        $this->revisions->setCurrentRevision($this->type, $revision);

        return $this->addNewAndRequested($slugs, $requested);
    }

    //endregion
}
