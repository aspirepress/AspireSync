<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Integrations\Wordpress;

use Saloon\Http\Connector;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\MemoryStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\HasTimeout;

class WordpressDownloadConnector extends Connector
{
    use HasTimeout;
    use HasRateLimits;

    protected int $connectTimeout = 10;
    protected int $requestTimeout = 300;

    public function resolveBaseUrl(): string
    {
        return 'https://downloads.wordpress.org';
    }

    public function defaultHeaders(): array
    {
        return [
            'User-Agent' => 'AspireSync/0.5; https://aspirepress.org', // downloads have no need to pretend
        ];
    }

    protected function defaultConfig(): array {
        return ['allow_redirects' => true];
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
