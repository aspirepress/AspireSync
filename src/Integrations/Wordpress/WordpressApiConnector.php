<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Integrations\Wordpress;

use Saloon\Http\Connector;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\MemoryStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\HasTimeout;

class WordpressApiConnector extends Connector
{
    use HasTimeout;
    // use HasRateLimits;   // too buggy with async requests to be at all usable

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
