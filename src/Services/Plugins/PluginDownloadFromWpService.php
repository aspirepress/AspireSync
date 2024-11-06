<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
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

    public function download(string $slug, array $versions, bool $force = false): array
    {
        $downloadable = $this->pluginMetadataService->getDownloadUrlsForVersions($slug, $versions);

        if (! $downloadable) {
            return [];
        }

        @mkdir('/opt/aspiresync/data/plugins');

        $outcomes = [];
        foreach ($downloadable as $version => $url) {
            $filePath = "/opt/aspiresync/data/plugins/$slug.$version.zip";

            if (file_exists($filePath) && ! $force) {
                $outcomes['304 Not Modified'][] = $version;
                $hash                           = $this->calculateHash($filePath);
                $this->pluginMetadataService->setVersionToDownloaded($slug, (string) $version, $hash);
                continue;
            }
            try {
                $response = $this->guzzle->request('GET', $url, ['allow_redirects' => true, 'sink' => $filePath]);
                $outcomes["{$response->getStatusCode()} {$response->getReasonPhrase()}"][] = $version;
                if (filesize($filePath) === 0) {
                    unlink($filePath);
                }
                $hash = $this->calculateHash($filePath);
                $this->pluginMetadataService->setVersionToDownloaded($slug, (string) $version, $hash);
            } catch (ClientException $e) {
                if (method_exists($e, 'getResponse')) {
                    $response = $e->getResponse();
                    $outcomes["{$response->getStatusCode()} {$response->getReasonPhrase()}"][] = $version;
                    if ($response->getStatusCode() === 404) {
                        $this->pluginMetadataService->setVersionToDownloaded($slug, (string) $version);
                    }
                } else {
                    $outcomes[$e->getMessage()][] = $version;
                }
                unlink($filePath);
            } catch (RuntimeException $e) {
                $outcomes[$e->getMessage()][] = $version;
                @unlink($filePath);
            }
        }

        return $outcomes;
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
