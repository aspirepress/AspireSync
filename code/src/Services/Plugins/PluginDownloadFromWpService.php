<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class PluginDownloadFromWpService implements DownloadServiceInterface
{
    /**
     * @param array<int, string> $userAgents
     */
    public function __construct(private array $userAgents, private PluginMetadataService $pluginMetadataService)
    {
        shuffle($this->userAgents);
    }

    /**
     * @inheritDoc
     */
    public function download(string $theme, array $versions, string $numToDownload = 'all', bool $force = false): array
    {
        $client       = new Client();
        $downloadFile = '/opt/aspiresync/data/plugins/%s.%s.zip';

        if (! file_exists('/opt/aspiresync/data/plugins')) {
            mkdir('/opt/aspiresync/data/plugins');
        }

        $outcomes     = [];
        $downloadable = $this->pluginMetadataService->getDownloadUrlsForVersions($theme, $versions);

        if (! $downloadable) {
            return $outcomes;
        }

        foreach ($downloadable as $version => $url) {
            $filePath = sprintf($downloadFile, $theme, $version);

            if (file_exists($filePath) && ! $force) {
                $outcomes['304 Not Modified'][] = $version;
                $hash                           = $this->calculateHash($filePath);
                $this->pluginMetadataService->setVersionToDownloaded($theme, (string) $version, $hash);
                continue;
            }
            try {
                $response = $client->request('GET', $url, ['headers' => ['User-Agent' => $this->userAgents[0]], 'allow_redirects' => true, 'sink' => $filePath]);
                $outcomes[$response->getStatusCode() . ' ' . $response->getReasonPhrase()][] = $version;
                if (filesize($filePath) === 0) {
                    unlink($filePath);
                }
                $hash = $this->calculateHash($filePath);
                $this->pluginMetadataService->setVersionToDownloaded($theme, (string) $version, $hash);
            } catch (ClientException $e) {
                if (method_exists($e, 'getResponse')) {
                    $response = $e->getResponse();
                    $outcomes[$response->getStatusCode() . ' ' . $response->getReasonPhrase()][] = $version;
                    if ($response->getStatusCode() === 404) {
                        $this->pluginMetadataService->setVersionToDownloaded($theme, (string) $version);
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
        $process = new Process([
            'unzip',
            '-t',
            $filePath,
        ]);
        $process->run();
        if ($process->isSuccessful()) {
            return hash_file('sha256', $filePath);
        }

        throw new RuntimeException($process->getErrorOutput());
    }
}
