<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Process\Process;

class ThemeListService
{
    private int $prevRevision = 0;

    private int $currentRevision;

    private array $oldThemeData = [];

    public function getThemeList(?array $filter = []): array
    {
        if (!$filter) {
            $filter = [];
        }

        $this->currentRevision = $this->identifyCurrentRevision();
        if (file_exists('/opt/asset-grabber/data/theme-data.json')) {
            $json = file_get_contents('/opt/asset-grabber/data/theme-data.json');
            $this->oldThemeData = json_decode($json, true);
            $this->prevRevision = $this->oldThemeData['meta']['my_revision'];
            return $this->filter($this->getThemesToUpdate($filter), $filter);
        }

        $themeList = $this->pullWholeThemeList();
        return $this->filter($themeList, $filter);
    }

    public function getVersionsForTheme($theme): array
    {
        if (!file_exists('/opt/asset-grabber/data/theme-raw-data')) {
            mkdir('/opt/asset-grabber/data/theme-raw-data');
        }

        if (file_exists('/opt/asset-grabber/data/theme-raw-data/' . $theme . '.json') && filemtime('/opt/asset-grabber/data/theme-raw-data/' . $theme . '.json') > time() - 3600) {
            $json = file_get_contents('/opt/asset-grabber/data/theme-raw-data/' . $theme . '.json');
            $data = json_decode($json, true);
            if (!isset($data['versions'])) {
                return [];
            }
            $themeData = array_keys($data['versions']);

        } else {
            $url    = 'https://api.wordpress.org/themes/info/1.2/';
            $queryParams = [
                'action' => 'theme_information',
                'slug' => $theme,
                'fields[]' => 'versions',
            ];
            $client = new Client();
            try {
                $response = $client->get($url, ['query' => $queryParams]);
                $data     = json_decode($response->getBody()->getContents(), true);
                file_put_contents(
                    '/opt/asset-grabber/data/theme-raw-data/' . $theme . '.json',
                    json_encode($data, JSON_PRETTY_PRINT)
                );
                $themeData = array_keys($data['versions']);
            } catch (ClientException $e) {
                if ($e->getCode() === 404) {
                    $content    = $e->getResponse()->getBody()->getContents();
                    file_put_contents('/opt/asset-grabber/data/theme-raw-data/' . $theme . '.json', $content);
                }

                return [];
            }
        }

        if (in_array('trunk', $themeData)) {
            $themeData = array_diff($themeData, ['trunk']);
        }

        return $themeData;
    }

    private function identifyCurrentRevision(): int
    {
    if (file_exists('/opt/asset-grabber/data/raw-changelog') && filemtime('/opt/asset-grabber/data/theme-raw-changelog') > time() - 3600) {
        $changelog = file_get_contents('/opt/asset-grabber/data/theme-raw-changelog');
    } else {
            try {
                $client = new Client();
                $changelog = $client->get(
                    'https://themes.trac.wordpress.org/log/?format=changelog&stop_rev=HEAD',
                    ['headers' => ['User-Agent' => 'AssetGrabber']]
                );
                $changelog = $changelog->getBody()->getContents();
                file_put_contents('/opt/asset-grabber/data/theme-raw-changelog', $changelog);
            } catch (\Exception $e) {
                throw new \RuntimeException('Unable to download changelog: ' . $e->getMessage());
            }
        }
        preg_match( '#\[([0-9]+)\]#', $changelog, $matches );
        return (int) $matches[1] ?? throw new \RuntimeException('Unable to parse last revision');

    }

    private function pullWholeThemeList(): array
    {
        if (file_exists('/opt/asset-grabber/data/raw-svn-theme-list') && filemtime('/opt/asset-grabber/data/raw-svn-theme-list') > time() - 3600) {
            $themes = file_get_contents('/opt/asset-grabber/data/raw-svn-theme-list');
        } else {
            try {
                $client  = new Client();
                $themes = $client->get('https://themes.svn.wordpress.org/', ['headers' => ['AssetGrabber']]);
                $contents = $themes->getBody()->getContents();
                file_put_contents('/opt/asset-grabber/data/raw-svn-theme-list', $contents);
                $themes = $contents;
            } catch (ClientException $e) {
                throw new \RuntimeException('Unable to download theme list: ' . $e->getMessage());
            }
        }
        preg_match_all( '#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $themes, $matches );
        $themes = $matches[1];

        $themesToReturn = [];
        foreach ($themes as $theme) {
            $themesToReturn[$theme] = [];
        }

        file_put_contents('/opt/asset-grabber/data/raw-theme-list', implode(PHP_EOL, $themes));

        return $themesToReturn;
    }

    private function getThemesToUpdate(array $explicitlyRequested = []): array
    {
        $lastRev = $this->oldThemeData['meta']['my_revision'];
        $targetRev = $lastRev + 1;
        $currentRev = $this->currentRevision;

        if ($this->currentRevision === $this->prevRevision) {
            return $this->mergeThemesToUpdate([], $explicitlyRequested);
        }

        if (file_exists('/opt/asset-grabber/data/theme-revision-' . $currentRev)) {
            $output = file('/opt/asset-grabber/data/theme-revision-' . $currentRev);
        } else {
            $command = [
                'svn',
                'log',
                '-v',
                '-q',
                'https://themes.svn.wordpress.org',
                "-r",
                "$targetRev:$currentRev"
            ];

            $process = new Process($command);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException('Unable to get list of themes to update' . $process->getErrorOutput());
            }

            $output = explode(PHP_EOL, $process->getOutput());
            file_put_contents('/opt/asset-grabber/data/revision-' . $currentRev, $process->getOutput());
        }

        $themesToUpdate = [];
        foreach ($output as $line) {
            preg_match('#^   [ADMR] /([^/(]+)/([^/(]+)/([^/(]+)/#', $line, $matches);
            if ($matches) {
                $theme = trim($matches[1]);
                if (trim($matches[2]) === 'tags' && ! empty($matches[3])) {
                    if (!isset($themesToUpdate[$theme])) {
                        $themesToUpdate[$theme] = [];
                    }

                    if (!in_array($matches[3], $themesToUpdate[$theme])) {
                        $themesToUpdate[$theme][] = $matches[3];
                    }
                }
            }
        }

        $themesToUpdate = $this->mergeThemesToUpdate($themesToUpdate, $explicitlyRequested);

        return $themesToUpdate;
    }

    private function mergeThemesToUpdate(array $themesToUpdate = [], array $explicitlyRequested = []): array
    {
        $allThemes = $this->pullWholeThemeList();

        foreach ($allThemes as $themeName => $themeVersion) {
            // Is this the first time we've seen the theme?
            if (!isset($this->oldThemeData['themes'][$themeName])) {
                $themesToUpdate[$themeName] = [];
            }

            if (in_array($themeName, $explicitlyRequested)) {
                $themesToUpdate[$themeName] = [];
            }
        }

        return $themesToUpdate;
    }

    public function preserveThemeList(array $themes): int|bool
    {
        if ($this->oldThemeData) {
            $toSave = [
                'meta' => [],
                'themes' => $this->oldThemeData['themes'],
            ];
            $toSave['themes'] = array_merge($toSave['themes'], $themes);
            $toSave['meta']['my_revision'] = $this->currentRevision;
        } else {
            $toSave = [
                'meta' => [
                    'my_revision' => $this->currentRevision,
                ],
                'themes' => [],
            ];

            $toSave['themes'] = array_merge($toSave['themes'], $themes);
        }

        return file_put_contents('/opt/asset-grabber/data/theme-data.json', json_encode($toSave, JSON_PRETTY_PRINT));
    }

    /**
     * Reduces the themes slated for update to only those specified in the filter.
     *
     * @param  array  $themes
     * @param  array|null  $filter
     * @return array
     */
    private function filter(array $themes, ?array $filter): array
    {
        if (! $filter) {
            return $themes;
        }

        $filtered = [];
        foreach ($filter as $theme) {
            if (array_key_exists($theme, $themes)) {
                $filtered[$theme] = $themes[$theme];
            }
        }

        return $filtered;
    }
}
