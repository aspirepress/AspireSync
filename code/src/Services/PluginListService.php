<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Process\Process;

class PluginListService
{
    private int $currentRevision;

    private array $oldPluginData = [];

    public function getPluginList(): array
    {
        $this->currentRevision = $this->identifyCurrentRevision();
        $oldPluginData = [];
        if (file_exists('/opt/plugin-slurp/data/plugin-data.json')) {
            $json = file_get_contents('/opt/plugin-slurp/data/plugin-data.json');
            $this->oldPluginData = json_decode($json, true);
            return $this->getPluginsToUpdate();
        }

        $pluginList = $this->pullWholePluginList();
        file_put_contents('/opt/plugin-slurp/data/raw-plugin-list', implode(PHP_EOL, array_keys($pluginList)));
        return $pluginList;
    }

    public function getVersionsForPlugin($plugin): array
    {
        $url = 'https://plugins.svn.wordpress.org/' . $plugin . '/tags/';
        $client = new Client();
        try {
            $response = $client->get($url);
            $versions = $response->getBody()->getContents();
            preg_match_all('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $versions, $matches);
            return $matches[1];
        } catch (ClientException $e) {
            return [];
        }
    }

    private function identifyCurrentRevision(): int
    {
        $client = new Client();
        $changelog = $client->get('https://plugins.trac.wordpress.org/log/?format=changelog&stop_rev=HEAD', ['headers' => ['User-Agent' => 'AssetGrabber']]);
        if (!$changelog) {
            throw new \RuntimeException('Unable to read last revision');
        }
        preg_match( '#\[([0-9]+)\]#', $changelog->getBody()->getContents(), $matches );
        return (int) $matches[1] ?? throw new \RuntimeException('Unable to parse last revision');

    }

    private function pullWholePluginList(): array
    {
        $client = new Client();
        $plugins = $client->get( 'https://plugins.svn.wordpress.org/', ['headers' => ['User-Agent' => 'AssetGrabber']] );
        if (! $plugins) {
            throw new \RuntimeException('Unable to download list of plugins');
        }

        preg_match_all( '#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $plugins->getBody()->getContents(), $matches );
        $plugins = $matches[1];

        $pluginsToReturn = [];
        foreach ($plugins as $plugin) {
            $pluginsToReturn[$plugin] = [];
        }

        return $pluginsToReturn;
    }

    private function getPluginsToUpdate(): array
    {
        $lastRev = $this->oldPluginData['meta']['my_revision'];
        $targetRev = $lastRev + 1;
        $currentRev = $this->currentRevision;

        $command = [
            'svn',
            'log',
            '-vq',
            'https://plugins.svn.wordpress.org',
            "-r $currentRev:$targetRev"
        ];

        $process = new Process($command);
        $process->run();
        $output = explode(PHP_EOL, $process->getOutput());
        $pluginsToUpdate = [];
        foreach ($output as $line) {
            preg_match('#^   [ADMR] /([^/(]+)/([^/(]+)/#', $line, $matches);
            $plugin = trim($matches[1]);
            if (trim($matches[2]) === 'tags') {
                $pluginsToUpdate[$plugin] = [];
            }
        }

        return $pluginsToUpdate;
    }

    public function preservePluginList(array $plugins): int|bool
    {
        if ($this->oldPluginData) {
            $toSave = array_merge($this->oldPluginData['plugins'], $plugins);
            $tosave['meta']['my_revision'] = $this->currentRevision;
        } else {
            $toSave = [
                'meta' => [
                    'my_revision' => $this->currentRevision,
                ],
                'plugins' => [],
            ];

            $toSave = array_merge($toSave['plugins'], $plugins);
        }

        return file_put_contents('/opt/plugin-slurp/data/plugin-data.json', json_encode($toSave));
    }
}