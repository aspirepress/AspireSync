<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Process\Process;

class ThemeDownloadFromWpService implements DownloadServiceInterface
{
    /**
     * @param array<int, string> $userAgents
     */
    public function __construct(private array $userAgents, private ThemesMetadataService $themeMetadataService)
    {
        shuffle($this->userAgents);
    }

    /**
     * @inheritDoc
     */
    public function download(string $theme, array $versions, string $numToDownload = 'all', bool $force = false): array
    {
        if (! file_exists('/opt/aspiresync/data/themes')) {
            mkdir('/opt/aspiresync/data/themes');
        }

        $outcomes     = [];
        $downloadable = $this->themeMetadataService->getDownloadUrlsForVersions($theme, $versions);

        if (! $downloadable) {
            return $outcomes;
        }

        foreach ($downloadable as $version => $url) {
            $result                        = $this->runDownload($theme, $version, $url, $force);
            $outcomes[$result['status']][] = $result['version'];
        }

        return $outcomes;
    }

    /**
     * @return string[]
     */
    private function runDownload(string $theme, string $version, string $url, bool $force): array
    {
        $client       = new Client();
        $downloadFile = '/opt/aspiresync/data/themes/%s.%s.zip';
        $filePath     = sprintf($downloadFile, $theme, $version);

        if (file_exists($filePath) && ! $force) {
            $hash = $this->calculateHash($filePath);
            $this->themeMetadataService->setVersionToDownloaded($theme, (string) $version, $hash);
            return ['status' => '304 Not Modified', 'version' => $version];
        }
        try {
            $response = $client->request('GET', $url, ['headers' => ['User-Agent' => $this->userAgents[0]], 'allow_redirects' => true, 'sink' => $filePath]);
            if (filesize($filePath) === 0) {
                unlink($filePath);
            }
            $hash = $this->calculateHash($filePath);
            $this->themeMetadataService->setVersionToDownloaded($theme, (string) $version, $hash);
        } catch (ClientException $e) {
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 404) {
                    $this->themeMetadataService->setVersionToDownloaded($theme, (string) $version);
                }
                if ($response->getStatusCode() === 429) {
                    sleep(2);
                    return $this->runDownload($theme, $version, $url, $force);
                }

                return ['status' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(), 'version' => $version];
            }

            unlink($filePath);
            return ['status' => $e->getMessage(), 'version' => $version];
        } catch (\RuntimeException $e) {
            $outcomes[$e->getMessage()][] = $version;
            @unlink($filePath);
        }

        return ['status' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(), 'version' => $version];
    }

    private function calculateHash(string $filePath): string
    {
        $process = new Process([
            'unzip',
            '-t',
            $filePath
        ]);
        $process->run();
        if ($process->isSuccessful()) {
            return hash_file('sha256', $filePath);
        }

        throw new \RuntimeException($process->getErrorOutput());
    }
}
