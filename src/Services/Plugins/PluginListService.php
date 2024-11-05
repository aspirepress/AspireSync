<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\ListServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\SvnServiceInterface;
use AspirePress\AspireSync\Services\RevisionMetadataService;

class PluginListService implements ListServiceInterface
{
    private int $prevRevision = 0;

    public function __construct(
        private SvnServiceInterface $svnService,
        private PluginMetadataService $pluginService,
        private RevisionMetadataService $revisionService,
    ) {
    }

    /**
     * @param array $filter
     * @param string $action
     * @param int|null $min_age
     * @return array<string, string[]>
     */
    public function getItemsForAction(array $filter, string $action, ?int $min_age = null): array
    {
        $lastRevision = $this->revisionService->getRevisionForAction($action);
        $updates      = $lastRevision
            ? $this->getPluginsToUpdate($filter, $lastRevision, $action)
            : $this->pullWholePluginList($action);
        return $this->filter($updates, $filter, $min_age);
    }

    /**
     * @param array<int, string> $explicitlyRequested
     * @return array<string, array<string>>
     */
    public function getUpdatedListOfItems(?array $explicitlyRequested, string $action = 'meta:download:plugins'): array
    {
        $revision = $this->revisionService->getRevisionDateForAction($action);
        if ($revision) {
            $revision = date('Y-m-d', strtotime($revision));
        }
        return $this->filter($this->pluginService->getVersionsForUnfinalizedPlugins($revision), $explicitlyRequested, null);
    }

    /**
     * @return array<string, string[]>
     */
    private function pullWholePluginList(string $action = 'default'): array
    {
        $result          = $this->svnService->pullWholeItemsList('plugins');
        $pluginsToReturn = $result['items'];
        $revision        = $result['revision'];
        $this->revisionService->setCurrentRevision($action, $revision);
        return $pluginsToReturn;
    }

    /**
     * @param array<int, string> $explicitlyRequested
     * @return array<string, string[]>
     */
    private function getPluginsToUpdate(?array $explicitlyRequested, string $lastRevision, string $action = 'default'): array
    {
        $output = $this->svnService->getRevisionForType('plugins', $this->prevRevision, (int) $lastRevision);

        $revision        = $output['revision'];
        $pluginsToUpdate = $output['items'];
        $this->revisionService->setCurrentRevision($action, $revision);

        return $this->addNewAndRequestedPlugins($action, $pluginsToUpdate, $explicitlyRequested);
    }

    /**
     * Takes the entire list of plugins, and adds any we have not seen before, plus merges plugins that we have explicitly
     * queued for update.
     *
     * @param array<int|string, string|string[]> $pluginsToUpdate
     * @param array<int, string> $explicitlyRequested
     * @return array<string, string[]>
     */
    private function addNewAndRequestedPlugins(string $action, array $pluginsToUpdate = [], ?array $explicitlyRequested = []): array
    {
        $allPlugins = $this->pullWholePluginList($action);

        foreach ($allPlugins as $pluginName => $pluginVersions) {
            // Is this the first time we've seen the plugin?
            if (! $this->pluginService->checkPluginInDatabase($pluginName)) {
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
                $timestamp = $this->pluginService->getPulledDateTimestamp($slug);
                if ($timestamp === null || $timestamp <= $cutoff) {
                    $out[$slug] = $value;
                }
            }
        }
        return $out;
    }

    public function preserveRevision(string $action): string
    {
        return $this->revisionService->preserveRevision($action);
    }
}
