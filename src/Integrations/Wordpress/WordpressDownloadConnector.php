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
    // use HasRateLimits;   // too buggy with async requests to be at all usable

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
}
