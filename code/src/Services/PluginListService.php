<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class PluginListService
{
    private int $prevRevision = 0;

    private int $currentRevision;

    /** @var array <string, string[]> */
    private array $oldPluginData = [];

    /**
     * @param string[]|null $filter
     * @return array<string, string[]>
     */
    public function getPluginList(?array $filter = []): array
    {
        if (! $filter) {
            $filter = [];
        }

        $this->currentRevision = $this->identifyCurrentRevision();
        if (file_exists('/opt/asset-grabber/data/plugin-data.json')) {
            $json                = file_get_contents('/opt/asset-grabber/data/plugin-data.json');
            $this->oldPluginData = json_decode($json, true);
            $this->prevRevision  = $this->oldPluginData['meta']['my_revision'];
            return $this->filter($this->getPluginsToUpdate($filter), $filter);
        }

        $pluginList = $this->pullWholePluginList();
        return $this->filter($pluginList, $filter);
    }

    /**
     * @return string[]
     */
    public function getVersionsForPlugin(string $plugin): array
    {
        if (! file_exists('/opt/asset-grabber/data/plugin-raw-data')) {
            mkdir('/opt/asset-grabber/data/plugin-raw-data');
        }

        if (file_exists('/opt/asset-grabber/data/plugin-raw-data/' . $plugin . '.json') && filemtime('/opt/asset-grabber/data/plugin-raw-data/' . $plugin . '.json') > time() - 3600) {
            $json = file_get_contents('/opt/asset-grabber/data/plugin-raw-data/' . $plugin . '.json');
            $data = json_decode($json, true);
            if (! isset($data['versions'])) {
                return [];
            }
            $pluginData = array_keys($data['versions']);
        } else {
            $url    = 'https://api.wordpress.org/plugins/info/1.0/' . $plugin . '.json';
            $client = new Client();
            try {
                $response = $client->get($url);
                $data     = json_decode($response->getBody()->getContents(), true);
                file_put_contents(
                    '/opt/asset-grabber/data/plugin-raw-data/' . $plugin . '.json',
                    json_encode($data, JSON_PRETTY_PRINT)
                );
                $pluginData = array_keys($data['versions']);
            } catch (ClientException $e) {
                if ($e->getCode() === 404) {
                    $content = $e->getResponse()->getBody()->getContents();
                    file_put_contents('/opt/asset-grabber/data/plugin-raw-data/' . $plugin . '.json', $content);
                }

                return [];
            }
        }

        if (in_array('trunk', $pluginData)) {
            $pluginData = array_diff($pluginData, ['trunk']);
        }

        return $pluginData;
    }

    private function identifyCurrentRevision(): int
    {
        if (file_exists('/opt/asset-grabber/data/raw-changelog') && filemtime('/opt/asset-grabber/data/raw-changelog') > time() - 3600) {
            $changelog = file_get_contents('/opt/asset-grabber/data/raw-changelog');
        } else {
            try {
                $client    = new Client();
                $changelog = $client->get(
                    'https://plugins.trac.wordpress.org/log/?format=changelog&stop_rev=HEAD',
                    ['headers' => ['User-Agent' => 'AssetGrabber']]
                );
                $changelog = $changelog->getBody()->getContents();
                file_put_contents('/opt/asset-grabber/data/raw-changelog', $changelog);
            } catch (Exception $e) {
                throw new RuntimeException('Unable to download changelog: ' . $e->getMessage());
            }
        }
        preg_match('#\[([0-9]+)\]#', $changelog, $matches);
        return (int) $matches[1];
    }

    /**
     * @return array<string, string[]>
     */
    private function pullWholePluginList(): array
    {
        if (file_exists('/opt/asset-grabber/data/raw-svn-plugin-list') && filemtime('/opt/asset-grabber/data/raw-svn-plugin-list') > time() - 3600) {
            $plugins = file_get_contents('/opt/asset-grabber/data/raw-svn-plugin-list');
        } else {
            try {
                $client   = new Client();
                $plugins  = $client->get('https://plugins.svn.wordpress.org/', ['headers' => ['AssetGrabber']]);
                $contents = $plugins->getBody()->getContents();
                file_put_contents('/opt/asset-grabber/data/raw-svn-plugin-list', $contents);
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

        file_put_contents('/opt/asset-grabber/data/raw-plugin-list', implode(PHP_EOL, $plugins));

        return $pluginsToReturn;
    }

    /**
     * @param string[] $explicitlyRequested
     * @return array<string, string[]>
     */
    private function getPluginsToUpdate(array $explicitlyRequested = []): array
    {
        $lastRev    = (int) $this->oldPluginData['meta']['my_revision'];
        $targetRev  = $lastRev + 1;
        $currentRev = $this->currentRevision;

        if ($this->currentRevision === $this->prevRevision) {
            return $this->mergePluginsToUpdate([], $explicitlyRequested);
        }

        if (file_exists('/opt/asset-grabber/data/revision-' . $currentRev)) {
            $output = file('/opt/asset-grabber/data/revision-' . $currentRev);
        } else {
            $command = [
                'svn',
                'log',
                '-v',
                '-q',
                'https://plugins.svn.wordpress.org',
                "-r",
                "$targetRev:$currentRev",
            ];

            $process = new Process($command);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('Unable to get list of plugins to update' . $process->getErrorOutput());
            }

            $output = explode(PHP_EOL, $process->getOutput());
            file_put_contents('/opt/asset-grabber/data/revision-' . $currentRev, $process->getOutput());
        }

        $pluginsToUpdate = [];
        foreach ($output as $line) {
            preg_match('#^   [ADMR] /([^/(]+)/([^/(]+)/([^/(]+)/#', $line, $matches);
            if ($matches) {
                $plugin = trim($matches[1]);
                if (trim($matches[2]) === 'tags' && ! empty($matches[3])) {
                    if (! isset($pluginsToUpdate[$plugin])) {
                        $pluginsToUpdate[$plugin] = [];
                    }

                    if (! in_array($matches[3], $pluginsToUpdate[$plugin])) {
                        $pluginsToUpdate[$plugin][] = $matches[3];
                    }
                }
            }
        }

        $pluginsToUpdate = $this->mergePluginsToUpdate($pluginsToUpdate, $explicitlyRequested);

        return $pluginsToUpdate;
    }

    /**
     * @param array<string, string[]> $pluginsToUpdate
     * @param string[] $explicitlyRequested
     * @return array<string, string[]>
     */
    private function mergePluginsToUpdate(array $pluginsToUpdate = [], array $explicitlyRequested = []): array
    {
        $allPlugins = $this->pullWholePluginList();

        foreach ($allPlugins as $pluginName => $pluginVersions) {
            // Is this the first time we've seen the plugin?
            if (! isset($this->oldPluginData['plugins'][$pluginName])) {
                $pluginsToUpdate[$pluginName] = [];
            }

            if (in_array($pluginName, $explicitlyRequested)) {
                $pluginsToUpdate[$pluginName] = [];
            }
        }

        return $pluginsToUpdate;
    }

    /**
     * @param array<string, string[]> $plugins
     */
    public function preservePluginList(array $plugins): int|bool
    {
        if ($this->oldPluginData) {
            $toSave                        = [
                'meta'    => [],
                'plugins' => $this->oldPluginData['plugins'],
            ];
            $toSave['plugins']             = array_merge($toSave['plugins'], $plugins);
            $toSave['meta']['my_revision'] = $this->currentRevision;
        } else {
            $toSave = [
                'meta'    => [
                    'my_revision' => $this->currentRevision,
                ],
                'plugins' => [],
            ];

            $toSave['plugins'] = array_merge($toSave['plugins'], $plugins);
        }

        return file_put_contents('/opt/asset-grabber/data/plugin-data.json', json_encode($toSave, JSON_PRETTY_PRINT));
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
}
