<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\Filesystem;

class PluginDownloadService implements DownloadServiceInterface
{
    public function __construct(
        private PluginMetadataService $pluginMetadataService,
        private GuzzleClient $guzzle,
        private Filesystem $filesystem,
    ) {
    }

    /** @return array<string,string[]> */
    public function download(string $slug, array $versions, bool $force = false): array
    {
        $downloadable = $this->pluginMetadataService->getDownloadUrlsForVersions($slug, $versions);

        if (! $downloadable) {
            return [];
        }

        $outcomes = [];
        foreach ($downloadable as $version => $url) {
            $outcome              = $this->runDownload($slug, (string)$version, $url, $force);
            $outcomes[$outcome] ??= [];
            $outcomes[$outcome][] = $version;
        }
        return $outcomes;
    }

    private function runDownload(string $slug, string $version, string $url, bool $force = false): string
    {
        $fs = $this->filesystem;
        $path = "/plugins/$slug.$version.zip";

        if ($fs->fileExists($path) && ! $force) {
            $this->pluginMetadataService->setVersionToDownloaded($slug, $version);
            return '304 Not Modified';
        }

        try {
            $options = ['headers' => ['User-Agent' => 'AspireSync/0.5'], 'allow_redirects' => true];
            $response = $this->guzzle->request('GET', $url, $options);
            $fs->write($path, $response->getBody()->getContents());
            // if ($fs->fileSize($path) === 0) {
            //     $fs->delete($path);
            // }
            $this->pluginMetadataService->setVersionToDownloaded($slug, $version);
            return "{$response->getStatusCode()} {$response->getReasonPhrase()}";
        } catch (ClientException $e) {
            $fs->delete($path);
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 404) {
                    $this->pluginMetadataService->setVersionToDownloaded($slug, $version);
                }
                return "{$response->getStatusCode()} {$response->getReasonPhrase()}";
            } else {
                return $e->getMessage();
            }
        } catch (Exception $e) {
            $fs->delete($path);
            return $e->getMessage();
        }
    }
}
