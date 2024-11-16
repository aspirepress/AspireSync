<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Integrations\Wordpress;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class WordpressApiConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 10;
    protected int $requestTimeout = 120;

    public function resolveBaseUrl(): string
    {
        return 'https://api.wordpress.org';
    }

    public function defaultHeaders(): array
    {
        return [
            'Accept'     => 'application/json',
            'User-Agent' => 'WordPress/6.6; https://example.org', // pretend to be WP because some responses are keyed on it
        ];
    }
}
