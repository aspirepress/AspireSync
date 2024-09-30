<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use AssetGrabber\Utilities\VersionUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class PluginDownloadService
{
    public function download($plugin, $versions, int|string $numToDownload = 'all')
    {
        $client = new Client();
        $downloadUrl = 'https://downloads.wordpress.org/plugin/%s.%s.zip?nostats=1';
        $downloadFile = '/opt/plugin-slurp/data/plugins/%s.%s.zip';

        if (!file_exists('/opt/plugin-slurp/data/plugins')) {
            mkdir('/opt/plugin-slurp/data/plugins');
        }

        switch ($numToDownload)
        {
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
            $url = sprintf($downloadUrl, $plugin, $version);
            $filePath = sprintf($downloadFile, $plugin, $version);
            try {
                $response = $client->request('GET', $url, ['headers' => ['User-Agent' => 'AssetGrabber'], 'allow_redirects' => true, 'sink' => $filePath]);
                $outcomes[$version] = $response->getStatusCode();
                if (filesize($filePath) === 0) {
                    unlink($filePath);
                    $outcomes[$version] = 'File was zero length; removed.';
                }
            } catch (ClientException $e) {
                $outcomes[$version] = $e->getMessage();
                unlink($filePath);
            }
        }

        return $outcomes;
    }
}
