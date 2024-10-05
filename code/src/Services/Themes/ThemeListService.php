<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Themes;

use AssetGrabber\Services\Interfaces\ListServiceInterface;
use AssetGrabber\Services\RevisionMetadataService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class ThemeListService implements ListServiceInterface
{
    private int $prevRevision = 0;

    public function __construct(private ThemesMetadataService $themesMetadataService, private RevisionMetadataService $revisionService)
    {
    }

    /**
     * @inheritDoc
     */
    public function getItemsForAction(array $filter, string $action): array
    {
        $lastRevision = 0;
        if ($this->revisionService->getRevisionForAction($action)) {
            $lastRevision = $this->revisionService->getRevisionForAction($action);
            return $this->filter($this->getThemesToUpdate($filter, $lastRevision, $action), $filter);
        }

        return $this->filter($this->pullWholeThemeList($action), $filter);
    }

    /**
     * @inheritDoc
     */
    public function getItemMetadata(string $item): array
    {
        if (! file_exists('/opt/assetgrabber/data/theme-raw-data')) {
            mkdir('/opt/assetgrabber/data/theme-raw-data');
        }

        $url         = 'https://api.wordpress.org/themes/info/1.2/';
        $queryParams = [
            'action'   => 'theme_information',
            'slug'     => $item,
            'fields[]' => 'versions',
        ];
        $client      = new Client();
        try {
            $response = $client->get($url, ['query' => $queryParams]);
            $data     = json_decode($response->getBody()->getContents(), true);
            file_put_contents(
                '/opt/assetgrabber/data/theme-raw-data/' . $item . '.json',
                json_encode($data, JSON_PRETTY_PRINT)
            );
            return $data;
        } catch (ClientException $e) {
            return ['error' => $e->getCode()];
        }
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedListOfItems(?array $explicitlyRequested): array
    {
        // TODO: Implement getUpdatedListOfItems() method.
    }

    public function preserveRevision(string $action): void
    {
        $this->revisionService->preserveRevision($action);
    }

    private function getThemesToUpdate(?array $explicitlyRequested, string $lastRevision, string $action = 'default'): array
    {
        $targetRev  = (int) $lastRevision;
        $currentRev = 'HEAD';

        if ($targetRev === $this->prevRevision) {
            return $this->addNewAndRequestedThemes($action, $explicitlyRequested, $explicitlyRequested);
        }

        $command = [
            'svn',
            'log',
            '-v',
            '-q',
            '--xml',
            'https://themes.svn.wordpress.org',
            "-r",
            "$targetRev:$currentRev",
        ];

        $process = new Process($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Unable to get list of themes to update' . $process->getErrorOutput());
        }

        $output  = simplexml_load_string($process->getOutput());
        $entries = $output->logentry;

        $themesToUpdate = [];
        $revision       = $lastRevision;
        foreach ($entries as $entry) {
            $revision = (int) $entry->attributes()['revision'];
            $path     = (string) $entry->paths->path[0];
            preg_match('#/([A-z\-_]+)/#', $path, $matches);
            if ($matches) {
                $theme                  = trim($matches[1]);
                $themesToUpdate[$theme] = [];
            }
        }

        $this->revisionService->setCurrentRevision($action, $revision);
        $themesToUpdate = $this->addNewAndRequestedThemes($action, $themesToUpdate, $explicitlyRequested);

        return $themesToUpdate;
    }

    /**
     * @return array<string, string[]>
     */
    private function pullWholeThemeList(string $action = 'default'): array
    {
        if (file_exists('/opt/assetgrabber/data/raw-svn-theme-list') && filemtime('/opt/assetgrabber/data/raw-svn-theme-list') > time() - 43200) {
            $themes   = file_get_contents('/opt/assetgrabber/data/raw-svn-theme-list');
            $contents = $themes;
        } else {
            try {
                $client   = new Client();
                $themes   = $client->get('https://themes.svn.wordpress.org/', ['headers' => ['User-Agent' => 'AssetGrabber']]);
                $contents = $themes->getBody()->getContents();
                file_put_contents('/opt/assetgrabber/data/raw-svn-theme-list', $contents);
                $themes = $contents;
            } catch (ClientException $e) {
                throw new RuntimeException('Unable to download theme list: ' . $e->getMessage());
            }
        }
        preg_match_all('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $themes, $matches);
        $themes = $matches[1];

        $themesToReturn = [];
        foreach ($themes as $theme) {
            $themesToReturn[$theme] = [];
        }

        preg_match('/Revision ([0-9]+)\:/', $contents, $matches);
        $revision = (int) $matches[1];

        file_put_contents('/opt/assetgrabber/data/raw-theme-list', implode(PHP_EOL, $themes));
        $this->revisionService->setCurrentRevision($action, $revision);
        return $themesToReturn;
    }

    /**
     * Takes the entire list of themes, and adds any we have not seen before, plus merges plugins that we have explicitly
     * queued for update.
     *
     * @param array<int|string, string|string[]> $themesToUpdate
     * @param array<int, string> $explicitlyRequested
     * @return array<string, string[]>
     */
    private function addNewAndRequestedThemes(string $action, array $themesToUpdate = [], ?array $explicitlyRequested = []): array
    {
        $allThemes = $this->pullWholeThemeList($action);

        foreach ($allThemes as $themeName => $themeVersion) {
            // Is this the first time we've seen the theme?
            if (! $this->themesMetadataService->checkThemeInDatabase($themeName)) {
                $themesToUpdate[$themeName] = [];
            }

            if (in_array($themeName, $explicitlyRequested)) {
                $themesToUpdate[$themeName] = [];
            }
        }

        return $themesToUpdate;
    }

    /**
     * Reduces the themes slated for update to only those specified in the filter.
     *
     * @param  array<string, string[]>  $themes
     * @param  array<int, string>|null  $filter
     * @return array<string, string[]>
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
