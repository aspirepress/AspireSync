<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use AssetGrabber\Utilities\VersionUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Output\OutputInterface;

class PluginDownloadFromWpService
{
    /**
     * @param array<int, string> $userAgents
     */
    public function __construct(private array $userAgents, private PluginMetadataService $pluginMetadataService)
    {
        shuffle($this->userAgents);
    }

    /**
     * @param string[] $versions
     * @return array<string, string[]>
     */
    public function download(string $plugin, array $versions, string $numToDownload = 'all', bool $force = false): array
    {
        $client       = new Client();
        $downloadFile = '/opt/assetgrabber/data/plugins/%s.%s.zip';

        if (! file_exists('/opt/assetgrabber/data/plugins')) {
            mkdir('/opt/assetgrabber/data/plugins');
        }

        list($versions, $outcomes) = $this->determineVersionsToDownload($plugin, $versions, $numToDownload);
        if (empty($versions) && !empty($outcomes)) {
            return $outcomes;
        }

        foreach ($versions as $version => $url) {

            $filePath = sprintf($downloadFile, $plugin, $version);

            if (file_exists($filePath) && ! $force) {
                $outcomes['304 Not Modified'][] = $version;
                continue;
            }
            try {
                $response = $client->request('GET', $url, ['headers' => ['User-Agent' => $this->userAgents[0]], 'allow_redirects' => true, 'sink' => $filePath]);
                $outcomes[$response->getStatusCode() . ' ' . $response->getReasonPhrase()][] = $version;
                if (filesize($filePath) === 0) {
                    unlink($filePath);
                }
                $this->pluginMetadataService->setVersionToDownloaded($plugin, $version);
            } catch (ClientException $e) {
                if (method_exists($e, 'getResponse')) {
                    $response = $e->getResponse();
                    $outcomes[$response->getStatusCode() . ' ' . $response->getReasonPhrase()][] = $version;
                    if ($response->getStatusCode() === 404) {
                        $this->pluginMetadataService->setVersionToDownloaded($plugin, $version);
                    }
                } else {
                    $outcomes[$e->getMessage()][] = $version;
                }
                unlink($filePath);
            }
        }

        return $outcomes;
    }

    private function determineVersionsToDownload($plugin, array $versions, string $numToDownload): array
    {
        switch ($numToDownload) {
            case 'all':
                $download = $versions;
                break;

            case 'latest':
                $download = [VersionUtil::getLatestVersion($versions)];
                break;

            default:
                $download = VersionUtil::limitVersions(VersionUtil::sortVersions($versions), (int) $numToDownload);
        }

        $downloadable = $this->pluginMetadataService->getUnprocessedVersions($plugin, $download);
        $outcome = [];
        if (empty($downloadable)) {
            $outcome['304 Not Modified'][] = $download;
        }

        return [$downloadable, $outcome];
    }
}