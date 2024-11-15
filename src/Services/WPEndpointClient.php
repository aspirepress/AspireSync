<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;

use Psr\Log\LoggerInterface;
use function Safe\json_decode;

class WPEndpointClient implements WpEndpointClientInterface
{
    public function __construct(
        private readonly GuzzleClient $guzzle,
        private readonly LoggerInterface $logger,
    ) {}

    /** @return array<string, mixed> */
    public function fetchMetadata(Resource $resource, string $slug): array
    {
        $method = match ($resource) {
            Resource::Plugin => $this->getPluginMetadata(...),
            Resource::Theme => $this->getThemeMetadata(...),
        };
        return $method($slug);
    }

    /** @return array<string, mixed> */
    protected function getPluginMetadata(string $slug): array
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
            $this->logger->debug('FETCHED',
                ['type' => 'plugin', 'slug' => $slug, 'status' => $response->getStatusCode()]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            try {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->logger->debug('ERROR IN FETCH', [
                    'type' => 'plugin',
                    'slug' => $slug,
                    'status' => $e->getResponse()->getStatusCode(),
                    'message' => $e->getMessage(),
                ]);
            } catch (Exception $e) {
                $body = ['error' => $e->getMessage()];
                $this->logger->debug('OTHER ERROR IN FETCH',
                    ['type' => 'plugin', 'slug' => $slug, 'message' => $e->getMessage()]);
            }
            $body['error'] ??= $e->getMessage();
            $status = match ($body['error']) {
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

    /** @return array<string, mixed> */
    protected function getThemeMetaData(string $slug): array
    {
        $url = 'https://api.wordpress.org/themes/info/1.2/';
        $queryParams = [
            'action' => 'theme_information',
            'slug' => $slug,
            'fields' => [
                'description',
                'sections',
                'rating',
                'ratings',
                'downloaded',
                'download_link',
                'last_updated',
                'homepage',
                'tags',
                'template',
                'parent',
                'versions',
                'screenshot_url',
                'active_installs',
            ],
        ];
        try {
            $response = $this->guzzle->get($url, ['query' => $queryParams]);
            $this->logger->debug('FETCHED',
                ['type' => 'theme', 'slug' => $slug, 'status' => $response->getStatusCode()]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            try {
                $body = json_decode($e->getResponse()->getBody()->getContents(), true);
                $this->logger->debug('ERROR IN FETCH', [
                    'type' => 'theme',
                    'slug' => $slug,
                    'status' => $e->getResponse()->getStatusCode(),
                    'message' => $e->getMessage(),
                ]);
            } catch (Exception $e) {
                $body = ['error' => $e->getMessage()];
                $this->logger->debug('OTHER ERROR IN FETCH',
                    ['type' => 'theme', 'slug' => $slug, 'message' => $e->getMessage()]);
            }
            $body['error'] ??= $e->getMessage();
            $status = match ($body['error']) {
                'Theme not found' => 'not-found', // note no period on this error message
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
}
