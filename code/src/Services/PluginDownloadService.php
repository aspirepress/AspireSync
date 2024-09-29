<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginDownloadService
{
    private array $outcomes = [];
    public function download($plugin, $versions, OutputInterface $output)
    {
        $client = new Client();
        $downloadUrl = 'https://downloads.wordpress.org/plugin/%s.%s.zip';
        $downloadFile = '/opt/plugin-slurp/data/plugins/%s.%s.zip';

        if (!file_exists('/opt/plugin-slurp/data/plugins')) {
            mkdir('/opt/plugin-slurp/data/plugins');
        }

        $promises = [];
        $outcomes = [];

        foreach ($versions as $version) {
            $url = sprintf($downloadUrl, $plugin, $version);
            $filePath = sprintf($downloadFile, $plugin, $version);
            try {
                $response = $client->request('GET', $url, ['headers' => ['User-Agent' => 'AssetGrabber'], 'allow_redirects' => true, 'sink' => $filePath]);
                $outcomes[$version] = $response->getStatusCode();
            } catch (ClientException $e) {
                $outcomes[$version] = $e->getMessage();
            }
        }

        return $outcomes;
    }
}