<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use AssetGrabber\Utilities\VersionUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class PluginDownloadService
{
    /**
     * @param string[] $versions
     * @return array<string, string[]>
     */
    public function download(string $plugin, array $versions, int|string $numToDownload = 'all', bool $force = false): array
    {
        $client       = new Client();
        $downloadUrl  = 'https://downloads.wordpress.org/plugin/%s.%s.zip?nostats=1';
        $downloadFile = '/opt/assetgrabber/data/plugins/%s.%s.zip';

        if (! file_exists('/opt/assetgrabber/data/plugins')) {
            mkdir('/opt/assetgrabber/data/plugins');
        }

        switch ($numToDownload) {
            case 'all':
                $download = $versions;
                break;

            case 'latest':
                $download = [VersionUtil::getLatestVersion($versions)];
                break;

            default:
                $download = VersionUtil::limitVersions(VersionUtil::sortVersions($versions), $numToDownload);
        }

        $outcomes = [];

        foreach ($download as $version) {
            $url      = sprintf($downloadUrl, $plugin, $version);
            $filePath = sprintf($downloadFile, $plugin, $version);

            if (file_exists($filePath) && ! $force) {
                $outcomes['304 Not Modified'][] = $version;
                continue;
            }
            try {
                $response = $client->request('GET', $url, ['headers' => ['User-Agent' => 'AssetGrabber'], 'allow_redirects' => true, 'sink' => $filePath]);
                $outcomes[$response->getStatusCode() . ' ' . $response->getReasonPhrase()][] = $version;
                if (filesize($filePath) === 0) {
                    unlink($filePath);
                    $outcomes[$version] = 'File was zero length; removed.';
                }
            } catch (ClientException $e) {
                if (method_exists($e, 'getResponse')) {
                    $response = $e->getResponse();
                    $outcomes[$response->getStatusCode() . ' ' . $response->getReasonPhrase()][] = $version;
                } else {
                    $outcomes[$e->getMessage()][] = $version;
                }
                unlink($filePath);
            }
        }

        return $outcomes;
    }
}
