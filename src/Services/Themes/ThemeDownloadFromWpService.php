<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\Filesystem;

class ThemeDownloadFromWpService implements DownloadServiceInterface
{
    public function __construct(
        private ThemesMetadataService $themeMetadataService,
        private GuzzleClient $guzzle,
        private Filesystem $filesystem,
    ) {
    }

    /** @return array<string, string>[] */
    public function download(string $slug, array $versions, bool $force = false): array
    {
        $downloadable = $this->themeMetadataService->getDownloadUrlsForVersions($slug, $versions);

        if (! $downloadable) {
            return [];
        }

        $outcomes = [];
        foreach ($downloadable as $version => $url) {
            $outcome              = $this->runDownload($slug, $version, $url, $force);
            $outcomes[$outcome] ??= [];
            $outcomes[$outcome][] = $version;
        }
        return $outcomes;
    }

    /**
     * @return array<string, string>
     */
    private function runDownload(string $slug, string $version, string $url, bool $force = false): string
    {
        $fs   = $this->filesystem;
        $path = "/themes/{$slug}.{$version}.zip";

        if ($fs->fileExists($path) && ! $force) {
            $this->themeMetadataService->setVersionToDownloaded($slug, $version);
            return '304 Not Modified';
        }
        try {
            $options  = ['headers' => ['User-Agent' => 'AspireSync/0.5'], 'allow_redirects' => true];
            $response = $this->guzzle->request('GET', $url, $options);
            $fs->write($path, $response->getBody()->getContents());
            if ($fs->fileSize($path) === 0) {
                $fs->delete($path);
            }
            $this->themeMetadataService->setVersionToDownloaded($slug, $version);
            return '200 OK';
        } catch (ClientException $e) {
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 404) {
                    $this->themeMetadataService->setVersionToDownloaded($slug, $version);
                }
                if ($response->getStatusCode() === 429) {
                    sleep(2);
                    return $this->runDownload($slug, $version, $url, $force);
                }

                return $response->getStatusCode() . ' ' . $response->getReasonPhrase();
            }

            $fs->delete($path);
            return $e->getMessage();
        } catch (Exception $e) {
            $fs->delete($path);
            return $e->getMessage();
        }

    }
}
