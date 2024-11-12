<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\AbstractListService;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\SubversionService;

class ThemeListService extends AbstractListService
{
    private int $prevRevision = 0;

    public function __construct(
        SubversionService $svn,
        ThemeMetadataService $meta,
        RevisionMetadataService $revisions,
    ) {
        parent::__construct($svn, $meta, $revisions);
    }

    public function getItemsForAction(array $filter, string $action, ?int $min_age = null): array
    {
        $lastRevision = $this->revisions->getRevisionForAction($action);
        $updates      = $lastRevision
            ? $this->getThemesToUpdate($filter, $lastRevision, $action)
            : $this->pullWholeThemeList($action);
        return $this->filter($updates, $filter, $min_age);
    }

    public function getUpdatedItems(?array $requested, string $action = 'meta:themes:download'): array
    {
        $revision = $this->revisions->getRevisionDateForAction($action);
        if ($revision) {
            $revision = date('Y-m-d', strtotime($revision));
        }
        return $this->filter(
            $this->meta->getVersionsForUnfinalizedThemes($revision),
            $requested,
            null
        );
    }

    public function preserveRevision(string $action): string
    {
        return $this->revisions->preserveRevision($action);
    }

    /**
     * @param string[] $requested
     * @return array<string, string[]>
     */
    private function getThemesToUpdate(?array $requested, string $lastRevision, string $action = 'default'): array
    {
        $output = $this->svn->getUpdatedSlugs('themes', $this->prevRevision, (int) $lastRevision);

        $revision = $output['revision'];
        $slugs    = $output['slugs'];
        $this->revisions->setCurrentRevision($action, $revision);

        return $this->addNewAndRequested($action, $slugs, $requested);
    }

    /** @return array<string, string[]> */
    private function pullWholeThemeList(string $action = 'default'): array
    {
        $result   = $this->svn->scrapeSlugsFromIndex('themes');
        $slugs    = $result['slugs'];
        $revision = $result['revision'];
        $this->revisions->setCurrentRevision($action, $revision);
        return $slugs;
    }

    /**
     * Takes the entire list of themes, and adds any we have not seen before,
     * plus merges themes that we have explicitly queued for update.
     *
     * @param array<int|string, string|string[]> $themesToUpdate
     * @param array<int, string> $requested
     * @return array<string, string[]>
     */
    private function addNewAndRequested(
        string $action,
        array $themesToUpdate = [],
        ?array $requested = [],
    ): array {
        $allThemes = $this->pullWholeThemeList($action);

        foreach ($allThemes as $themeName => $themeVersion) {
            // Is this the first time we've seen the theme?
            $themeName = (string) $themeName;
            if (! $this->meta->checkThemeInDatabase($themeName)) {
                $themesToUpdate[$themeName] = [];
            }

            if (in_array($themeName, $requested, true)) {
                $themesToUpdate[$themeName] = [];
            }
        }

        return $themesToUpdate;
    }

    /**
     * Reduces the themes slated for update to only those specified in the filter.
     *
     * @param array<string, string[]> $themes
     * @param array<int, string>|null $filter
     * @return array<string, string[]>
     */
    private function filter(array $themes, ?array $filter, ?int $min_age): array
    {
        if (! $filter && ! $min_age) {
            return $themes;
        }

        $filtered = $filter ? [] : $themes;

        foreach ($filter as $slug) {
            if (array_key_exists($slug, $themes)) {
                $filtered[$slug] = $themes[$slug];
            }
        }

        $out = $min_age ? [] : $filtered;
        if ($min_age) {
            $cutoff = time() - $min_age;
            foreach ($filtered as $slug => $value) {
                $slug      = (string) $slug; // purely numeric names get turned into int
                $timestamp = $this->meta->getPulledDateTimestamp($slug);
                if ($timestamp === null || $timestamp <= $cutoff) {
                    $out[$slug] = $value;
                }
            }
        }
        return $out;
    }
}
