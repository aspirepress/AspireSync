<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\ListServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\SubversionServiceInterface;
use AspirePress\AspireSync\Services\RevisionMetadataService;

class PluginListService implements ListServiceInterface
{
    private int $prevRevision = 0;

    public function __construct(
        private SubversionServiceInterface $svn,
        private PluginMetadataService $meta,
        private RevisionMetadataService $revisions,
    ) {
    }

    /**
     * @param string[] $filter
     * @return array<string, string[]>
     */
    public function getItemsForAction(array $filter, string $action, ?int $min_age = null): array
    {
        $lastRevision = $this->revisions->getRevisionForAction($action);
        $updates      = $lastRevision
            ? $this->getPluginsToUpdate($filter, $lastRevision, $action)
            : $this->pullWholePluginList($action);
        return $this->filter($updates, $filter, $min_age);
    }

    /**
     * @param array<int, string> $requested
     * @return array<string, string[]>
     */
    public function getUpdatedListOfItems(?array $requested, string $action = 'meta:plugins:download'): array
    {
        $revDate = $this->revisions->getRevisionDateForAction($action);
        if ($revDate) {
            $revDate = date('Y-m-d', strtotime($revDate));
        }
        return $this->filter($this->meta->getOpenVersions($revDate), $requested, null);
    }

    public function preserveRevision(string $action): string
    {
        return $this->revisions->preserveRevision($action);
    }

    //region Private API

    /** @return array<string, string[]> */
    private function pullWholePluginList(string $action = 'default'): array
    {
        $result   = $this->svn->pullWholeItemsList('plugins');
        $slugs    = $result['slugs'];
        $revision = $result['revision'];
        $this->revisions->setCurrentRevision($action, $revision);
        return $slugs;
    }

    /**
     * @param string[] $requested
     * @return array<string, string[]>
     */
    private function getPluginsToUpdate(?array $requested, string $lastRevision, string $action = 'default'): array
    {
        $output = $this->svn->getUpdatedSlugs('plugins', $this->prevRevision, (int) $lastRevision);

        $revision = $output['revision'];
        $slugs    = $output['slugs'];
        $this->revisions->setCurrentRevision($action, $revision);

        return $this->addNewAndRequested($action, $slugs, $requested);
    }

    /**
     * Takes the entire list of plugins, and adds any we have not seen before,
     * plus merges plugins that we have explicitly queued for update.
     *
     * @param array<int|string, string|string[]> $pluginsToUpdate
     * @param array<int, string> $explicitlyRequested
     * @return array<string, string[]>
     */
    private function addNewAndRequested(string $action, array $pluginsToUpdate = [], ?array $explicitlyRequested = []): array
    {
        $allPlugins = $this->pullWholePluginList($action);

        foreach ($allPlugins as $pluginName => $pluginVersions) {
            // Is this the first time we've seen the plugin?
            if (! $this->meta->has($pluginName)) {
                $pluginsToUpdate[$pluginName] = [];
            }

            if (in_array($pluginName, $explicitlyRequested, true)) {
                $pluginsToUpdate[$pluginName] = [];
            }
        }

        return $pluginsToUpdate;
    }

    /**
     * Reduces the plugins slated for update to only those specified in the filter.
     *
     * @param  array<string, string[]>  $plugins
     * @param  array<int, string>|null  $filter
     * @return array<string, string[]>
     */
    private function filter(array $plugins, ?array $filter, ?int $min_age): array
    {
        if (! $filter && ! $min_age) {
            return $plugins;
        }

        $filtered = $filter ? [] : $plugins;

        foreach ($filter as $slug) {
            if (array_key_exists($slug, $plugins)) {
                $filtered[$slug] = $plugins[$slug];
            }
        }

        $out = $min_age ? [] : $filtered;
        if ($min_age) {
            $cutoff = time() - $min_age;
            foreach ($filtered as $slug => $value) {
                $timestamp = $this->meta->getPulledDateTimestamp($slug);
                if ($timestamp === null || $timestamp <= $cutoff) {
                    $out[$slug] = $value;
                }
            }
        }
        return $out;
    }

    //endregion
}
