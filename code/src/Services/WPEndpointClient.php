<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use AssetGrabber\Services\Interfaces\WpEndpointClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class WPEndpointClient implements WpEndpointClientInterface
{
    public function getPluginMetadata(string $plugin): string
    {
        $url    = 'https://api.wordpress.org/plugins/info/1.0/' . $plugin . '.json';
        $client = new Client();
        try {
            $response = $client->get($url);
            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            if ($e->getCode() === 404) {
                return $e->getResponse()->getBody()->getContents();
            }
        }

        return '';
    }

    public function getThemeMetadata(string $theme): string
    {
        return '';
    }

    public function downloadFile(string $url, string $destination): string
    {
        return '';
    }
}
