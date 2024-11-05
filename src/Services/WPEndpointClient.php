<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use function Safe\json_decode;

class WPEndpointClient implements WpEndpointClientInterface
{
    public function __construct(private readonly GuzzleClient $guzzle) {}

    public function getPluginMetadata(string $slug): array
    {
        $url = 'https://api.wordpress.org/plugins/info/1.2/';
        $queryParams = [
            'action' => 'plugin_information',
            'slug' => $slug,
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
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            try {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
            } catch (\Exception $e) {
                $body = ['error' => $e->getMessage()];
            }
            $body['error'] ??= $e->getMessage();
            $status = match($body['error']) {
                'Plugin not found.' => 'not-found',
                'closed' => 'closed',
                default => 'error',
            };
            return [
                ...$body,
                'slug' => $slug,
                'name' => $slug,
                'status' => $status,
            ];
        }
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
