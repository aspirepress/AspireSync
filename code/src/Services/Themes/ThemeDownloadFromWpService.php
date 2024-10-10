<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

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
        if (! file_exists('/opt/assetgrabber/data/themes')) {
            mkdir('/opt/assetgrabber/data/themes');
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
        $downloadFile = '/opt/assetgrabber/data/themes/%s.%s.zip';
        $filePath     = sprintf($downloadFile, $theme, $version);

        if (file_exists($filePath) && ! $force) {
            $this->themeMetadataService->setVersionToDownloaded($theme, (string) $version);
            return ['status' => '304 Not Modified', 'version' => $version];
        }
        try {
            $response = $client->request('GET', $url, ['headers' => ['User-Agent' => $this->userAgents[0]], 'allow_redirects' => true, 'sink' => $filePath]);
            if (filesize($filePath) === 0) {
                unlink($filePath);
            }
            $this->themeMetadataService->setVersionToDownloaded($theme, (string) $version);
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
        }

        return ['status' => $response->getStatusCode() . ' ' . $response->getReasonPhrase(), 'version' => $version];
    }
}
