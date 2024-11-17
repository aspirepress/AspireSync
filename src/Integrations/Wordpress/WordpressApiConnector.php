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
    use HasRateLimits;

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

    protected function resolveLimits(): array
    {
        // limit is quite high, we're mostly just interested in the transparent handling of 429 responses
        return [
            Limit::allow(20)->everySeconds(1)->sleep(),
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new MemoryStore;
    }
}
