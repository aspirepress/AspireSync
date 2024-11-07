<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class PluginDownloadFromWpService implements DownloadServiceInterface
{
    public function __construct(
        private PluginMetadataService $pluginMetadataService,
        private GuzzleClient $guzzle,
    ) {
    }

    /** @return array<string,string[]> */
    public function download(string $slug, array $versions, bool $force = false): array
    {
        $downloadable = $this->pluginMetadataService->getDownloadUrlsForVersions($slug, $versions);

        if (! $downloadable) {
            return [];
        }

        @mkdir('/opt/aspiresync/data/plugins');

        $outcomes = [];
        foreach ($downloadable as $version => $url) {
            $outcome              = $this->downloadOne($url, $slug, $version, $force);
            $outcomes[$outcome] ??= [];
            $outcomes[$outcome][] = $version;
        }
        return $outcomes;
    }

    private function downloadOne(string $url, string $slug, string $version, bool $force = false): string
    {
        $filePath = "/opt/aspiresync/data/plugins/$slug.$version.zip";

        if (file_exists($filePath) && ! $force) {
            $hash = $this->calculateHash($filePath);
            $this->pluginMetadataService->setVersionToDownloaded($slug, $version, $hash);
            return '304 Not Modified';
        }

        try {
            $response = $this->guzzle->request('GET', $url, ['headers' => ['User-Agent' => 'AspireSync/0.5'], 'allow_redirects' => true, 'sink' => $filePath]);
            if (filesize($filePath) === 0) {
                unlink($filePath);
            }
            $this->pluginMetadataService->setVersionToDownloaded($slug, $version, $this->calculateHash($filePath));
            return "{$response->getStatusCode()} {$response->getReasonPhrase()}";
        } catch (ClientException $e) {
            @unlink($filePath);
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
            @unlink($filePath);
            return $e->getMessage();
        }
    }

    private function calculateHash(string $filePath): string
    {
        $process = new Process(['unzip', '-t', $filePath]);
        $process->run();
        return $process->isSuccessful()
            ? hash_file('sha256', $filePath)
            : throw new RuntimeException($process->getErrorOutput());
    }
}
