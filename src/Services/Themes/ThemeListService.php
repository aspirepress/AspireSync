<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\Interfaces\ListServiceInterface;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Utilities\FileUtil;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

use function Safe\filemtime;
use function Safe\json_decode;

class ThemeListService implements ListServiceInterface
{
    private int $prevRevision = 0;

    public function __construct(
        private ThemesMetadataService $themesMetadataService,
        private RevisionMetadataService $revisionService,
        private GuzzleClient $guzzle,
    ) {
    }

    public function getItemsForAction(array $filter, string $action): array
    {
        if ($lastRevision = $this->revisionService->getRevisionForAction($action)) {
            return $this->filter($this->getThemesToUpdate($filter, $lastRevision, $action), $filter);
        }
        return $this->filter($this->pullWholeThemeList($action), $filter);
    }

    public function getItemMetadata(string $slug): array
    {
        if ($this->isNotFound($slug)) {
            return ['skipped' => "$slug previously marked not found; skipping..."];
        }

        $url         = 'https://api.wordpress.org/themes/info/1.2/';
        $queryParams = [
            'action' => 'theme_information',
            'slug'   => $slug,
            'fields' => [
                'description',
                'sections',
                'rating',
                'ratings',
                'downloaded',
                'download_link',
                'last_updated',
                'homepage',
                'tags',
                'template',
                'parent',
                'versions',
                'screenshot_url',
                'active_installs',
            ],
        ];
        try {
            $response = $this->guzzle->get($url, ['query' => $queryParams]);
            $data     = json_decode($response->getBody()->getContents(), true);
            $filename = "/opt/aspiresync/data/theme-raw-data/{$slug}.json";
            FileUtil::writeJson($filename, $data);
            return $data;
        } catch (ClientException $e) {
            return ['error' => $e->getCode()];
        }
    }

    public function getUpdatedListOfItems(?array $explicitlyRequested, string $action = 'meta:download:themes'): array
    {
        $revision = $this->revisionService->getRevisionDateForAction($action);
        if ($revision) {
            $revision = date('Y-m-d', strtotime($revision));
        }
        return $this->filter(
            $this->themesMetadataService->getVersionsForUnfinalizedThemes($revision),
            $explicitlyRequested
        );
    }

    public function preserveRevision(string $action): void
    {
        $this->revisionService->preserveRevision($action);
    }

    /**
     * @param array<int, string> $explicitlyRequested
     * @return array<string, string[]>
     */
    private function getThemesToUpdate(
        ?array $explicitlyRequested,
        string $lastRevision,
        string $action = 'default',
    ): array {
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
        $filename = '/opt/aspiresync/data/raw-svn-theme-list';
        if (file_exists($filename) && filemtime($filename) > time() - 43200) {
            $themes   = FileUtil::read($filename);
            $contents = $themes;
        } else {
            try {
                $themes   = $this->guzzle->get(
                    'https://themes.svn.wordpress.org/',
                    ['headers' => ['User-Agent' => 'AspireSync']]
                );
                $contents = $themes->getBody()->getContents();
                FileUtil::write($filename, $contents);
                $themes = $contents;
            } catch (ClientException $e) {
                throw new RuntimeException('Unable to download theme list: ' . $e->getMessage());
            }
        }
        preg_match_all('#<li><a href="([^/]+)/">([^/]+)/</a></li>#', $themes, $matches);
        $themes = $matches[1];

        $themesToReturn = [];
        foreach ($themes as $theme) {
            $themesToReturn[(string) $theme] = [];
        }

        preg_match('/Revision ([0-9]+)\:/', $contents, $matches);
        $revision = (int) $matches[1];

        $filename = '/opt/aspiresync/data/raw-theme-list';
        FileUtil::writeLines($filename, $themes);
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
    private function addNewAndRequestedThemes(
        string $action,
        array $themesToUpdate = [],
        ?array $explicitlyRequested = [],
    ): array {
        $allThemes = $this->pullWholeThemeList($action);

        foreach ($allThemes as $themeName => $themeVersion) {
            // Is this the first time we've seen the theme?
            $themeName = (string) $themeName;
            if (! $this->themesMetadataService->checkThemeInDatabase($themeName) && ! $this->isNotFound($themeName)) {
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
     * @param array<string, string[]> $themes
     * @param array<int, string>|null $filter
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

    public function isNotFound(string $item): bool
    {
        return $this->themesMetadataService->isNotFound($item);
    }

    public function markItemNotFound(string $item): void
    {
        $this->themesMetadataService->markItemNotFound($item);
    }
}
