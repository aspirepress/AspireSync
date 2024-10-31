<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Utilities\FileUtil;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use function Safe\json_decode;

class WPEndpointClient implements WpEndpointClientInterface
{
    public function __construct(private readonly GuzzleClient $guzzle) {}

    public function getPluginMetadata(string $plugin): string
    {
        $url = 'https://api.wordpress.org/plugins/info/1.2/';
        $queryParams = [
            'action' => 'plugin_information',
            'slug' => $plugin,
            'fields' => [
                'active_installs',
                'added',
                'author',
                'author_block_count',
                'author_block_rating',
                'author_profile',
                'banners',
                'compatibility',
                'contributors',
                'description',
                'donate_link',
                'download_link',
                'downloaded',
                'homepage',
                'icons',
                'last_updated',
                'name',
                'num_ratings',
                'rating',
                'ratings',
                'requires',
                'requires_php',
                'screenshots',
                'sections',
                'short_description',
                'slug',
                'support_threads',
                'support_threads_resolved',
                'tags',
                'tested',
                'version',
                'versions',
            ],
        ];

        try {
            $response = $this->guzzle->get($url, ['query' => $queryParams]);
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
