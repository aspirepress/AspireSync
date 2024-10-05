<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Plugins;

use AssetGrabber\Services\Interfaces\ListServiceInterface;
use AssetGrabber\Services\RevisionMetadataService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class PluginListService implements ListServiceInterface
{
    private int $prevRevision = 0;

    public function __construct(private PluginMetadataService $pluginService, private RevisionMetadataService $revisionService)
    {
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

        $url    = 'https://api.wordpress.org/plugins/info/1.0/' . $item . '.json';
        $client = new Client();
        try {
            $response = $client->get($url);
            $data     = json_decode($response->getBody()->getContents(), true);
            file_put_contents(
                '/opt/assetgrabber/data/plugin-raw-data/' . $item . '.json',
                json_encode($data, JSON_PRETTY_PRINT)
            );
            return $data;
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                $content = $e->getResponse()->getBody()->getContents();
                file_put_contents('/opt/assetgrabber/data/plugin-raw-data/' . $item . '.json', $content);
                return json_decode($content, true);
            }
        }

        return [];
    }

    /**
     * @param array<int, string> $explicitlyRequested
     * @return array<string, array<string>>
     */
    public function getUpdatedListOfItems(?array $explicitlyRequested): array
    {
        return $this->filter($this->pluginService->getVersionsForUnfinalizedPlugins(), $explicitlyRequested);
    }

    /**
     * @return array<string, string>
     */
    public function getVersionsForItem(string $item): array
    {
        $data = $this->getItemMetadata($item);

        if (isset($data['versions'])) {
            $pluginData = $data['versions'];
        } elseif (isset($data['version'])) {
            $pluginData = [$data['version'] => $data['download_link']];
        } else {
            return [];
        }

        if (isset($pluginData['trunk'])) {
            unset($pluginData['trunk']);
        }

        return $pluginData;
    }

    public function identifyCurrentRevision(bool $force = false): int
    {
        if (! $force && file_exists('/opt/assetgrabber/data/raw-changelog') && filemtime('/opt/assetgrabber/data/raw-changelog') > time() - 86400) {
            $output = file_get_contents('/opt/assetgrabber/data/raw-changelog');
        } else {
            $command = [
                'svn',
                'log',
                '-v',
                '-q',
                'https://plugins.svn.wordpress.org',
                "-r",
                "HEAD",
            ];

            $process = new Process($command);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Unable to get list of plugins to update' . $process->getErrorOutput());
            }

            $output = $process->getOutput();

            file_put_contents('/opt/assetgrabber/data/raw-changelog', $output);
        }

        $output = explode(PHP_EOL, $output);
        preg_match('/([0-9]+) \|/', $output[1], $matches);
        $this->prevRevision = (int) $matches[1];
        return (int) $matches[1];
    }

    /**
     * @return array<string, string[]>
     */
    private function pullWholePluginList(string $action = 'default'): array
    {
        if (file_exists('/opt/assetgrabber/data/raw-svn-plugin-list') && filemtime('/opt/assetgrabber/data/raw-svn-plugin-list') > time() - 86400) {
            $plugins  = file_get_contents('/opt/assetgrabber/data/raw-svn-plugin-list');
            $contents = $plugins;
        } else {
            try {
                $client   = new Client();
                $plugins  = $client->get('https://plugins.svn.wordpress.org/', ['headers' => ['AssetGrabber']]);
                $contents = $plugins->getBody()->getContents();
                file_put_contents('/opt/assetgrabber/data/raw-svn-plugin-list', $contents);
                $plugins = $contents;
            } catch (ClientException $e) {
                throw new RuntimeException('Unable to download plugin list: ' . $e->getMessage());
            }
        }
        preg_match_all('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $plugins, $matches);
        $plugins = $matches[1];

        $pluginsToReturn = [];
        foreach ($plugins as $plugin) {
            $pluginsToReturn[$plugin] = [];
        }

        preg_match('/Revision ([0-9]+)\:/', $contents, $matches);
        $revision = (int) $matches[1];

        file_put_contents('/opt/assetgrabber/data/raw-plugin-list', implode(PHP_EOL, $plugins));
        $this->revisionService->setCurrentRevision($action, $revision);
        return $pluginsToReturn;
    }

    /**
     * @param array<int, string> $explicitlyRequested
     * @return array<string, string[]>
     */
    private function getPluginsToUpdate(?array $explicitlyRequested, string $lastRevision, string $action = 'default'): array
    {
        $targetRev  = (int) $lastRevision;
        $currentRev = 'HEAD';

        if ($targetRev === $this->prevRevision) {
            return $this->addNewAndRequestedPlugins($action, $explicitlyRequested, $explicitlyRequested);
        }

        $command = [
            'svn',
            'log',
            '-v',
            '-q',
            '--xml',
            'https://plugins.svn.wordpress.org',
            "-r",
            "$targetRev:$currentRev",
        ];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Unable to get list of plugins to update' . $process->getErrorOutput());
        }

        $output  = simplexml_load_string($process->getOutput());
        $entries = $output->logentry;

        $pluginsToUpdate = [];
        $revision        = $lastRevision;
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
            if (! $this->pluginService->checkPluginInDatabase($pluginName)) {
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
}
