<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Symfony\Component\Process\Process;

class ThemeDownloadFromWpService implements DownloadServiceInterface
{
    public function __construct(
        private ThemesMetadataService $themeMetadataService,
        private GuzzleClient $guzzle,
    ) {
    }

    /** @return array<string, string>[] */
    public function download(string $theme, array $versions, bool $force = false): array
    {
        @mkdir('/opt/aspiresync/data/themes');

        $downloadable = $this->themeMetadataService->getDownloadUrlsForVersions($theme, $versions);

        if (! $downloadable) {
            return [];
        }

        $outcomes = [];
        foreach ($downloadable as $version => $url) {
            $result                        = $this->runDownload($theme, $version, $url, $force);
            $outcomes[$result['status']][] = $result['version'];
        }
        return $outcomes;
    }

    /**
     * @return array<string, string>
     */
    private function runDownload(string $theme, string $version, string $url, bool $force): array
    {
        $filePath = "/opt/aspiresync/data/themes/{$theme}.{$version}.zip";

        if (file_exists($filePath) && ! $force) {
            $hash = $this->calculateHash($filePath);
            $this->themeMetadataService->setVersionToDownloaded($theme, $version, $hash);
            return ['status' => '304 Not Modified', 'version' => $version];
        }
        try {
            $this->guzzle->request('GET', $url, ['allow_redirects' => true, 'sink' => $filePath]);
            if (filesize($filePath) === 0) {
                @unlink($filePath);
            }
            $hash = $this->calculateHash($filePath);
            $this->themeMetadataService->setVersionToDownloaded($theme, $version, $hash);
        } catch (ClientException $e) {
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 404) {
                    $this->themeMetadataService->setVersionToDownloaded($theme, $version);
                }
                if ($response->getStatusCode() === 429) {
                    sleep(2);
                    return $this->runDownload($theme, $version, $url, $force);
                }

                return ['status' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(), 'version' => $version];
            }

            @unlink($filePath);
            return ['status' => $e->getMessage(), 'version' => $version];
        } catch (RuntimeException $e) {
            @unlink($filePath);
            return ['status' => $e->getMessage(), 'version' => $version];
        }

        return [];
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
