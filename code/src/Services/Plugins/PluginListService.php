<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Plugins;

use AssetGrabber\Services\Interfaces\ListServiceInterface;
use AssetGrabber\Services\Interfaces\SvnServiceInterface;
use AssetGrabber\Services\Interfaces\WpEndpointClientInterface;
use AssetGrabber\Services\RevisionMetadataService;

class PluginListService implements ListServiceInterface
{
    private int $prevRevision = 0;

    public function __construct(
        private SvnServiceInterface $svnService,
        private PluginMetadataService $pluginService,
        private RevisionMetadataService $revisionService,
        private WpEndpointClientInterface $wpClient
    ) {
    }

    /**
     * @param  array<int, string>  $filter
     * @return array<string, string[]>
     */
    public function getItemsForAction(array $filter, string $action): array
    {
        $lastRevision = 0;
        if ($this->revisionService->getRevisionForAction($action)) {
            $lastRevision = $this->revisionService->getRevisionForAction($action);
            return $this->filter($this->getPluginsToUpdate($filter, $lastRevision, $action), $filter);
        }

        return $this->filter($this->pullWholePluginList($action), $filter);
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function getItemMetadata(string $item): array
    {
        if (! file_exists('/opt/assetgrabber/data/plugin-raw-data')) {
            mkdir('/opt/assetgrabber/data/plugin-raw-data');
        }

        if ($this->isNotFound($item)) {
            return [
                'skipped' => $item . ' previously marked not found; skipping...',
            ];
        }

        $output = $this->wpClient->getPluginMetadata($item);

        file_put_contents('/opt/assetgrabber/data/plugin-raw-data/' . $item . '.json', $output);

        return json_decode($output, true);
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
        return $this->filter($this->pluginService->getVersionsForUnfinalizedPlugins($revision), $explicitlyRequested);
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
        $output          = $this->svnService->getRevisionForType('plugins', (int) $this->prevRevision, (int) $lastRevision);
        $pluginsToUpdate = [];
        if ($output) {
            $entries = $output->logentry;

            $revision = $lastRevision;
            foreach ($entries as $entry) {
                $revision = (int) $entry->attributes()['revision'];
                $path     = (string) $entry->paths->path[0];
                preg_match('#/([A-z\-_]+)/#', $path, $matches);
                if ($matches) {
                    $plugin                   = trim($matches[1]);
                    $pluginsToUpdate[$plugin] = [];
                }
            }

            $this->revisionService->setCurrentRevision($action, $revision);
        }

        $pluginsToUpdate = $this->addNewAndRequestedPlugins($action, $pluginsToUpdate, $explicitlyRequested);

        return $pluginsToUpdate;
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
            if (! $this->pluginService->checkPluginInDatabase($pluginName) && ! $this->isNotFound($pluginName)) {
                $pluginsToUpdate[$pluginName] = [];
            }

            if (in_array($pluginName, $explicitlyRequested)) {
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
    private function filter(array $plugins, ?array $filter): array
    {
        if (! $filter) {
            return $plugins;
        }

        $filtered = [];
        foreach ($filter as $plugin) {
            if (array_key_exists($plugin, $plugins)) {
                $filtered[$plugin] = $plugins[$plugin];
            }
        }

        return $filtered;
    }

    public function preserveRevision(string $action): void
    {
        $this->revisionService->preserveRevision($action);
    }

    public function isNotFound(string $item): bool
    {
        return $this->pluginService->isNotFound($item);
    }

    public function markItemNotFound(string $item): void
    {
        $this->pluginService->markItemNotFound($item);
    }
}
