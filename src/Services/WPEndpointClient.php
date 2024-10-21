<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

class WPEndpointClient implements WpEndpointClientInterface
{
    public function __construct(private readonly GuzzleClient $guzzle) {}

    public function getPluginMetadata(string $plugin): string
    {
        $url    = 'https://api.wordpress.org/plugins/info/1.0/' . $plugin . '.json';
        try {
            $response = $this->guzzle->get($url);
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
