<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use AssetGrabber\Utilities\VersionUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class ThemeDownloadService
{
    public function download(string $theme, array $versions, int|string $numToDownload = 'all', bool $force = false): array
    {
        $client       = new Client();
        $downloadUrl  = 'https://downloads.wordpress.org/theme/%s.%s.zip?nostats=1';
        $downloadFile = '/opt/asset-grabber/data/themes/%s.%s.zip';

        if (! file_exists('/opt/asset-grabber/data/themes')) {
            mkdir('/opt/asset-grabber/data/themes');
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
            $url      = sprintf($downloadUrl, $theme, $version);
            $filePath = sprintf($downloadFile, $theme, $version);

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
